-- Create the initial DGM user
-- Run this SQL script in your database

INSERT INTO users (
    name, 
    email, 
    employee_id, 
    password, 
    user_role, 
    status, 
    user_location, 
    created_at, 
    updated_at
) VALUES (
    'Deputy General Manager',
    'dgm@nebula.com',
    'DGM001',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: dgm123456
    'DGM',
    '1',
    'Nebula Institute of Technology – Welisara',
    NOW(),
    NOW()
);

-- Note: The password hash above is for 'dgm123456'
-- You can generate a new hash using: php artisan tinker
-- Then run: echo Hash::make('your_password_here'); 