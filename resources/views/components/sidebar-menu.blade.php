@php
    use App\Helpers\RoleHelper;
    $roles = auth()->user() ? auth()->user()->getRoleList() : [];
    $sections = config('menu.sections');
@endphp

<ul id="sidebarnav">
@foreach($sections as $section)
    @php
        // Determine if any items in this section are visible to this user
        $visible = collect($section['items'])->contains(function($item) use ($roles) {
            if(isset($item['config']) && !config($item['config'])) {
                return false;
            }
            if(isset($item['roles'])) {
                return RoleHelper::hasAnyRole($roles, $item['roles']);
            }
            if(isset($item['permission'])) {
                return RoleHelper::hasPermission($roles, $item['permission']);
            }
            // Also ensure a named route exists for the item when provided
            if(isset($item['route'])) {
                return Route::has($item['route']);
            }
            return true;
        });
    @endphp

    @if($visible)
        <li class="nav-small-cap">
            <span class="nav-small-cap-text">{{ $section['title'] }}</span>
        </li>

        @foreach($section['items'] as $item)
            @php
                $hasAccess = true;
                if(isset($item['config']) && !config($item['config'])) {
                    $hasAccess = false;
                } elseif(isset($item['roles'])) {
                    $hasAccess = RoleHelper::hasAnyRole($roles, $item['roles']);
                } elseif(isset($item['permission'])) {
                    $hasAccess = RoleHelper::hasPermission($roles, $item['permission']);
                }
            @endphp

            @if($hasAccess)
                <li class="sidebar-item">
                    @if(isset($item['is_profile']) && $item['is_profile'])
                        @php
                            $user = auth()->user();
                            $studentId = $user->student_id ?? 0;
                            $url = route('student_management.profile', ['studentId' => $studentId]);
                            $active = request()->routeIs('student_management.profile');
                        @endphp
                        <a class="sidebar-link {{ $active ? 'active' : '' }}" href="{{ $url }}">
                            <span><i class="{{ $item['icon'] }}"></i></span>
                            <span class="hide-menu">{{ $item['label'] }}</span>
                        </a>
                    @else
                        @php
                            $active = request()->routeIs($item['route']) || Route::currentRouteName() == $item['route'];
                            $routeExists = isset($item['route']) && Route::has($item['route']);
                        @endphp

                        @if($routeExists)
                            <a class="sidebar-link {{ $active ? 'active' : '' }}" href="{{ route($item['route']) }}">
                                <span><i class="{{ $item['icon'] }}"></i></span>
                                <span class="hide-menu">{{ $item['label'] }}</span>
                            </a>
                        @else
                            <a class="sidebar-link disabled" href="#" title="Route '{{ $item['route'] ?? 'undefined' }}' not found">
                                <span><i class="{{ $item['icon'] }}"></i></span>
                                <span class="hide-menu">{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endif
                </li>
            @endif
        @endforeach
    @endif
@endforeach

<hr class="my-2 border-gray-200 opacity-30">

<li class="px-3 pb-3">
    <div class="bg-light rounded p-3 d-flex flex-column gap-2 align-items-center">
        <a href="{{ route('user.profile') }}" class="btn w-100" style="background-color: #6c8cff; color: #fff; font-weight: 500;">My Profile</a>
        <a href="{{ route('logout') }}" class="btn w-100" style="background-color: #ff8c7a; color: #fff; font-weight: 500;">Logout</a>
    </div>
</li>

<li id="teamNebulaLink" class="text-center mb-3" style="opacity: 0.8; font-size: 13px;">
    <a href="{{ route('team.phase.index') }}" class="text-decoration-none d-inline-block py-1 px-2 rounded {{ Route::currentRouteName() == 'team.phase.index' ? 'bg-light text-primary fw-semibold shadow-sm' : 'text-muted' }}" style="transition: all 0.3s;">© Team Nebula IT</a>
</li>
