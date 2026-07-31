<?php

return [
    'verify' => [
        'subject' => 'Verify your email address',
        'greeting' => 'Hello!',
        'intro' => 'Please click the button below to verify your email address.',
        'button' => 'Verify Email Address',
        'expiry' => 'This verification link will expire in :count minutes. If it has expired, you can request a new one from the app.',
        'outro' => 'If you did not create an account, no further action is required.',
        'salutation' => 'The :app Team',
    ],

    'reset' => [
        'subject' => 'Reset your password',
        'greeting' => 'Hello!',
        'intro' => 'You are receiving this email because we received a password reset request for your account.',
        'button' => 'Reset Password',
        'expiry' => 'This password reset link will expire in :count minutes. If it has expired, you can request a new one.',
        'outro' => 'If you did not request a password reset, no further action is required.',
        'salutation' => 'The :app Team',
    ],
];
