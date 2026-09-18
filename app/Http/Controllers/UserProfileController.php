<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // Import the Hash facade
use Illuminate\Validation\Rules\Password; // Import the Password rule
use App\Models\User;
use App\Http\Requests\CreateUserRequest;
use App\Helpers\RoleHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserProfileController extends Controller
{
    public function showUserProfile()
    {
        // Get the authenticated user's ID
        $user_id = Auth::id();

        // Fetch user details by user_id from the User model
        $user = User::findOrFail($user_id);

        // Get the current user's user_location
        $current_user_location = $user->user_location ?? 0;

        // Fetch users based only on user_location
        $users = User::where('user_location', $current_user_location)->get();

        // Prepare the array with user details
        $usersArray = $users->map(function ($user) {
            $displayRoles = implode(', ', $user->getRoleList());

            return [
                'user_id' => $user->user_id,
                'user_name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'user_role' => $displayRoles !== '' ? $displayRoles : 'N/A',
                'status' => ($user->status == "1" ? "Active" : ($user->status == "0" ? "Inactive" : ($user->status == "2" ? "Suspended" : "Unknown"))),
                'user_location' => $user->user_location ?? 'Unknown'
            ];
        });

        // Prepare user data to pass to the view
        $userData = [
            'user_name' => $user->name,
            'email' => $user->email,
            'employee_id' => $user->employee_id,
            'user_role' => implode(', ', $user->getRoleList()),
            'status' => ($user->status == "1" ? "Active" : ($user->status == "0" ? "Inactive" : ($user->status == "2" ? "Suspended" : "Unknown"))),
            'user_location' => $this->formatCampusLocation($user->user_location),
            'user_profile' => $user->user_profile ?? null,
        ];

        // Fetch all user roles from RoleHelper
        $userRoles = array_keys(RoleHelper::getRoles());

        // Add hardcoded locations array for dropdown
        $locations = [
            'Nebula Institute of Technology – Welisara',
            'Nebula Institute of Technology – Moratuwa',
            'Nebula Institute of Technology – Peradeniya'
        ];

        //dd($usersArray);

        // Pass user data and users array to the view
        return view('user_profile', [
            'userData' => $userData,
            'usersArray' => $usersArray->toArray(),
            'userRoles' => $userRoles,
            'locations' => $locations,
        ]);
    }

    public function deleteUser(Request $request)
{
    $user = User::find($request->user_id);

    if (!$user) {
        return response()->json(['success' => false, 'message' => 'User not found.']);
    }

    // Perform actual deletion or soft delete
    $user->delete();

    return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
}
    
public function updateUserStatus(Request $request)
{
    $validRoles = array_keys(RoleHelper::getRoles());

    // Validate the request data
    $request->validate([
        'user_id' => 'required|exists:users,user_id',
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email,' . $request->user_id . ',user_id',
        'user_roles' => ['required_without:user_role', 'array', 'min:1'],
        'user_roles.*' => ['required', 'string', \Illuminate\Validation\Rule::in($validRoles)],
        'user_role' => ['required_without:user_roles', 'string', \Illuminate\Validation\Rule::in($validRoles)],
        'status' => 'required|in:"0","1","2"', // 0 (Inactive), 1 (Active), 2 (Suspended)
        'user_location' => 'required|string', // Validate location as string
    ]);

    try {
        // Find the user by user_id
        $user = User::findOrFail($request->user_id);

        $updatedRoles = $request->input('user_roles', []);
        if (empty($updatedRoles) && $request->filled('user_role')) {
            $updatedRoles = [$request->user_role];
        }

        $updatedRoles = RoleHelper::normalizeRoles($updatedRoles);
        $primaryRole = RoleHelper::resolvePrimaryRole($updatedRoles);

        if (empty($updatedRoles) || !$primaryRole) {
            return response()->json(['success' => false, 'message' => 'At least one valid role is required.'], 422);
        }

        // Update user information
        $user->name = $request->name;
        $user->email = $request->email;
        $user->employee_id = $request->employee_id;
        $user->user_role = $primaryRole;
        $user->user_roles = $updatedRoles;
        $user->user_location = $this->formatCampusLocation($request->user_location);
        $user->status = $request->status;
        $user->save();

        // Return a success response
        return response()->json(['success' => true, 'message' => 'User information updated successfully']);
    } catch (\Exception $e) {
        // Return an error response if something goes wrong
        return response()->json(['success' => false, 'message' => 'Failed to update user information', 'error' => $e->getMessage()], 500);
    }
}





    // Method to create a new user
    // Remove the createNewUser and showCreateUserForm methods if not used elsewhere
    // Only keep user listing, edit, and delete logic for DGM user management

    public function changePassword(Request $request)
{
    try {
        // Validate the incoming request
        $request->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'min:6', 'confirmed'], // 'confirmed' checks for new_password_confirmation
        ]);

        $userId = Auth::id();
        $user = User::findOrFail($userId);

        // 1️⃣ Check if the current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Your current password is incorrect.'
            ], 400);
        }

        // 2️⃣ Check if new and confirm password match (extra check, just in case)
        if ($request->new_password !== $request->new_password_confirmation) {
            return response()->json([
                'success' => false,
                'message' => 'New password and confirm password do not match.'
            ], 400);
        }

        // 3️⃣ Update the password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.'
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Validation errors (e.g., missing fields or short password)
        $firstError = collect($e->errors())->flatten()->first();
        return response()->json([
            'success' => false,
            'message' => $firstError ?? 'Invalid input.'
        ], 422);
    } catch (\Exception $e) {
        // General error fallback
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while changing the password.',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Reset a user's password (admin action)
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'new_password' => ['required', 'string', 'min:6'],
        ]);

        $admin = Auth::user();
        if (!$admin->hasAnyRole(['DGM', 'Program Administrator (level 01)', 'Developer'])) {
            abort(403, 'Unauthorized');
        }

        $user = User::findOrFail($request->user_id);
        $user->password = Hash::make($request->new_password);
        $user->save();

        return redirect()->back()->with('success', 'Password reset successfully for ' . $user->name . '.');
    }

    // New method for DGM user management
    public function showDGMUserManagement()
    {
        // Get the authenticated user
        $currentUser = Auth::user();
        
        // Check if user is Program Administrator (level 01) or Developer
        if (!$currentUser->hasAnyRole(['Program Administrator (level 01)', 'Developer'])) {
            abort(403, 'Access denied. Only Program Administrator (level 01) and Developer can access this page.');
        }

        // Fetch all users for management
        $users = User::all();

        // Prepare the array with user details
        $usersArray = $users->map(function ($user) {
            $displayRoles = implode(', ', $user->getRoleList());
            $createdAt = $user->createdAtSriLanka();
            $updatedAt = $user->updatedAtSriLanka();

            return [
                'user_id' => $user->user_id,
                'user_name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'user_role' => $displayRoles !== '' ? $displayRoles : 'N/A',
                'status' => ($user->status == "1" ? "Active" : ($user->status == "0" ? "Inactive" : ($user->status == "2" ? "Suspended" : "Unknown"))),
                'user_location' => $this->formatCampusLocation($user->user_location),
                'created_at' => $createdAt ? $createdAt->format('Y-m-d H:i') : 'N/A',
                'updated_at' => $updatedAt ? $updatedAt->format('Y-m-d H:i') : 'N/A'
            ];
        });

        // Fetch all user roles from RoleHelper
        $userRoles = array_keys(RoleHelper::getRoles());

        return view('user_management.index', [
            'usersArray' => $usersArray->toArray(),
            'userRoles' => $userRoles,
            'locations' => $this->campusLocations(),
        ]);
    }

    // Show the Create User form (GET)
    public function showCreateUserForm()
    {
        $currentUser = Auth::user();
        if (!$currentUser->hasAnyRole(['DGM', 'Program Administrator (level 01)', 'Developer'])) {
            abort(403, 'Access denied. Only DGM, Program Administrator (level 01), and Developer can access this page.');
        }
        $userRoles = array_keys(RoleHelper::getRoles());
        $locations = [
            'Nebula Institute of Technology – Welisara',
            'Nebula Institute of Technology – Moratuwa',
            'Nebula Institute of Technology – Peradeniya'
        ];
        return view('user_management.create_user', [
            'userRoles' => $userRoles,
            'locations' => $locations,
        ]);
    }

    // Method to get user details for editing
    public function getUserDetails(Request $request)
    {
        try {
            $user = User::findOrFail($request->user_id);
            
            return response()->json([
                'success' => true,
                'user' => [
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_id' => $user->employee_id,
                    'user_role' => $user->user_role,
                    'user_roles' => $user->getRoleList(),
                    'status' => $user->status,
                    'user_location' => $user->user_location,
                    'user_location_key' => $this->campusKey($user->user_location),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
    }

    // Handle user creation (POST)
    public function createUser(CreateUserRequest $request)
{
    try {
        // Laravel automatically throws ValidationException if validation fails
        $validated = $request->validated();

        // Check again manually for email uniqueness (extra safety)
        if (User::where('email', $validated['email'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'The email has already been taken.'
            ], 422);
        }

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->employee_id = $validated['employee_id'];
        $assignedRoles = RoleHelper::normalizeRoles($validated['user_roles'] ?? ($validated['user_role'] ?? null));
        $primaryRole = RoleHelper::resolvePrimaryRole($assignedRoles);

        if (empty($assignedRoles) || !$primaryRole) {
            return response()->json([
                'success' => false,
                'message' => 'At least one valid user role is required.'
            ], 422);
        }

        $user->user_role = $primaryRole;
        $user->user_roles = $assignedRoles;
        $user->status = 1; // Default Active
        $user->user_location = $validated['user_location'];
        $user->password = Hash::make($validated['password']);
        $user->save();

        Log::info('User created successfully', [
            'created_by' => Auth::user()->user_id,
            'new_user_id' => $user->user_id,
            'new_user_email' => $user->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.'
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // Return JSON error list instead of redirect
        return response()->json([
            'success' => false,
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Failed to create user', [
            'error' => $e->getMessage(),
            'created_by' => Auth::user()->user_id,
            'request_data' => $request->except(['password'])
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create user.',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Update the authenticated user's profile picture
     */
    public function updateProfilePicture(Request $request)
    {
        try {
            $request->validate([
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
            }

            $file = $request->file('profile_picture');
            if (!$file) {
                return response()->json(['success' => false, 'message' => 'Please choose an image to upload.'], 422);
            }

            Storage::disk('public')->makeDirectory('profile_pictures');
            $path = $file->store('profile_pictures', 'public');

            if (!empty($user->user_profile) && Storage::disk('public')->exists($user->user_profile)) {
                try {
                    Storage::disk('public')->delete($user->user_profile);
                } catch (\Throwable $e) {
                    Log::warning('Failed to delete previous profile picture', [
                        'user_id' => $user->user_id,
                        'path' => $user->user_profile,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $user->user_profile = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully.',
                'url' => asset('storage/' . ltrim($path, '/')),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();

            return response()->json([
                'success' => false,
                'message' => $firstError ?? 'Invalid profile picture.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update profile picture', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile picture. Please try again.',
            ], 500);
        }
    }

    private function campusLocations(): array
    {
        return [
            'Welisara' => 'Nebula Institute of Technology – Welisara',
            'Moratuwa' => 'Nebula Institute of Technology – Moratuwa',
            'Peradeniya' => 'Nebula Institute of Technology – Peradeniya',
        ];
    }

    private function campusKey($storedLocation): string
    {
        $text = trim((string) $storedLocation);

        foreach (array_keys($this->campusLocations()) as $campus) {
            if ($text !== '' && stripos($text, $campus) !== false) {
                return $campus;
            }
        }

        return $text;
    }

    private function formatCampusLocation($storedLocation): string
    {
        $text = trim((string) $storedLocation);
        if ($text === '') {
            return 'Unknown';
        }

        $key = $this->campusKey($text);

        return $this->campusLocations()[$key] ?? $text;
    }
}
