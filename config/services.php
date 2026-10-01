<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'srru' => [
        'email_domain' => env('SRRU_EMAIL_DOMAIN', 'srru.ac.th'),
        // Off by default — the install shortcut binds to whatever URL is
        // open when the user taps "install" (see pwa-install-banner.blade.php),
        // so it's only safe to offer once the app is sitting on its final,
        // stable domain. Flip to true once deployed there (no redeploy
        // needed, just set the env var and the change takes effect).
        'pwa_install_prompt_enabled' => env('PWA_INSTALL_PROMPT_ENABLED', false),

        // Static office-contact details shown on the "ติดต่อเรา" page
        // alongside the chat (see partials/contact-info-panel.blade.php).
        // Phone/email/address are left unset by default rather than
        // guessed — the view hides whichever of these three isn't
        // configured instead of showing a placeholder. Set the real values
        // via .env when deploying; no code change needed.
        'office_phone' => env('SRRU_OFFICE_PHONE'),
        'office_email' => env('SRRU_OFFICE_EMAIL'),
        'office_address' => env('SRRU_OFFICE_ADDRESS'),
        'office_hours' => env('SRRU_OFFICE_HOURS', 'จันทร์–ศุกร์ 08:30–16:30 น. (ยกเว้นวันหยุดราชการ)'),
        'response_time' => env('SRRU_RESPONSE_TIME', 'โดยปกติตอบกลับภายใน 1–2 วันทำการ'),

        // Passwords for the "เข้าสู่ระบบแบบทดลอง" form (see
        // Auth\DemoLoginController) used by evaluators who can't sign in
        // with a university Google account — whichever one is typed decides
        // which demo account they get. Leave both unset to turn the feature
        // off entirely (its routes 404), so it's opt-in per deployment.
        'demo_admin_password' => env('DEMO_ADMIN_PASSWORD'),
        'demo_student_password' => env('DEMO_STUDENT_PASSWORD'),
    ],

];
