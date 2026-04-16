# Resume Creator (PHP + TXT Database)

This project is a PHP website for creating resumes with:

- **Bootstrap 5** UI
- **jQuery** interactions
- **Font Awesome** icons
- File-based storage using **TXT files**

## Features

- User registration and login
- Users stored in `data/users/users.txt`
- Each user has their own TXT data file (`data/users/<username>.txt`)
- Resume editor dashboard
- 10 selectable resume templates
- Resume preview page
- PDF download via browser print-to-PDF (jQuery triggers print)

## Run locally

From the repository root:

```bash
php -S localhost:8000
```

Then open:

- `http://localhost:8000/register.php` to create account
- `http://localhost:8000/login.php` to login

## Notes

- Passwords are stored securely as hashes.
- Templates are style variants (`Template 1` to `Template 10`) selectable from the dashboard.
- Download PDF uses browser native print dialog (`window.print()`).
