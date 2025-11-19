# Used Engine Pro — Rent A Car Landing Page

This project is a static landing page with a lead generation reservation widget and a small PHP endpoint to send reservation details via SMTP (PHPMailer). It uses Tailwind CSS for styling and vanilla JavaScript for interactivity.

Files:
- `index.html` — Main landing page with reservation widget, location autosuggest, and AJAX submission.
- `send_mail.php` — PHP endpoint that accepts JSON POST and sends email using PHPMailer (Gmail SMTP by default).
- `thankyou.html` — Simple confirmation page.

Quick setup
1. Install PHP and Composer (if not already installed).
2. From the project folder, install PHPMailer:

```powershell
cd 'C:\Users\VRezoLV\Desktop\rent'
composer require phpmailer/phpmailer
```

3. Configure environment variables locally (preferred) or edit `send_mail.php` constants.

Recommended environment variables:
- MAIL_HOST (default: smtp.gmail.com)
- MAIL_USERNAME (your Gmail address)
- MAIL_PASSWORD (your Gmail App Password)
- MAIL_PORT (default: 587)
- MAIL_SECURE (tls or ssl)
- MAIL_TO (admin email to receive leads)
- MAIL_DRY_RUN (set to `1` or `true` to skip sending during testing)

On Windows PowerShell you can set env vars for the current session like this:

```powershell
$env:MAIL_USERNAME = 'youremail@gmail.com'
$env:MAIL_PASSWORD = 'YOUR_APP_PASSWORD'
$env:MAIL_TO = 'admin@yourcompany.com'
$env:MAIL_DRY_RUN = '1'
```

4. Start PHP's built-in server for local testing:

```powershell
php -S localhost:8000
```

5. Open `http://localhost:8000/index.html` in your browser, fill in the form and submit. If `MAIL_DRY_RUN` is set, no email will be sent and the endpoint will return success for testing.

Notes & production considerations
- The front-end uses OpenStreetMap Nominatim for location autosuggest. Nominatim has usage policies and rate limits — for production use a paid geocoding/autocomplete service (Google Places, Mapbox, HERE, etc.).
- Do not commit real credentials. Prefer environment variables or a secrets manager.
- For production email delivery, consider an SMTP provider (SendGrid, Mailgun, SES) for reliability.
- Add server-side rate limiting or CAPTCHA to reduce spam.

If you want, I can:
- Wire Google Places instead of Nominatim (requires your API key).
- Replace the env var usage with a `.env` loader and sample `.env.example`.
- Add server-side logging of leads to a CSV or SQLite database.

