A simple, ready-to-use contact form.
It uses Cloudflare Turnstile for protection and PHPMailer to send the data.
contact-form.js 
1. Run composer install / update to get all dependencies (PHPMailer + vlucas/phpdotenv)
2. Configure env.txt variables and copy to .env
3. Get cloudflare turnstile tokens to activate protection
4. Include html markup in your own HTML or add some styles and content to contact-form.html
5. If you wish change $minSubmitTime value to desired min time in seconds.
