<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'Invalid username or password. Please try again.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    
    // Custom authentication messages
    'account_inactive' => 'Your account is not active. Please contact administrator.',
    'no_role_assigned' => 'Your account does not have a valid role assigned. Please contact administrator.',
    'login_success' => 'Welcome back!',
    'logout_success' => 'You have been successfully logged out.',
    'session_expired' => 'Your session has expired. Please log in again.',
    'unauthorized' => 'Unauthorized access. Please log in.',
    'invalid_credentials' => 'Invalid username or password. Please try again.',
    'too_many_attempts' => 'Too many login attempts. Please try again later.',
    'account_locked' => 'Your account has been temporarily locked due to multiple failed login attempts.',
    'login_required' => 'Please log in to access this page.',
    'access_denied' => 'You do not have permission to access this resource.',
    'server_error' => 'An error occurred during login. Please try again.',

];
