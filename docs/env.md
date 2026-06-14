# Environment Variables

Load `.env` files and read their values through a global `env()` helper, with type coercion and fallbacks.

## Setup

Create a `.env` file in your project root directory:

```ini
# .env
KIRBY_DEBUG=true
API_SECRET_KEY=your-secret-key-here
DATABASE_URL=mysql://user:pass@localhost/mydb
MAIL_FROM=noreply@example.com
```

> [!IMPORTANT]
> Add `.env` to your `.gitignore` file to prevent committing sensitive data to your repository.

## Usage

### In Templates and Snippets

Use the global `env()` function or the site method:

```php
// Global helper function
$apiKey = env('API_SECRET_KEY');
$debugMode = env('KIRBY_DEBUG', false); // with fallback

// Site method
$mailFrom = $site->env('MAIL_FROM', 'default@example.com');
```

> [!NOTE]
> The global `env()` helper only reads variables that are already loaded – it does not read the `.env` file itself. The `$site->env()` method loads the file on its first call. In `config.php`, load it manually with `Env::load()` (see below) before relying on the global `env()`.

### In Configuration Files

For use in `config.php`, manually load the environment before Kirby initializes:

```php
// config.php
$base = dirname(__DIR__, 2);
\JohannSchopplich\Helpers\Env::load($base);

return [
    'debug' => env('KIRBY_DEBUG', false),
    'email' => [
        'from' => env('MAIL_FROM')
    ],
    'api' => [
        'secret' => env('API_SECRET_KEY')
    ]
];
```

## Value Parsing

The helper automatically converts common values:

- `true`, `(true)` → `true` (boolean)
- `false`, `(false)` → `false` (boolean)
- `null`, `(null)` → `null`
- `empty`, `(empty)` → `""` (empty string)
- `"quoted strings"` → removes surrounding quotes

## Configuration Options

| Option                                  | Default                 | Description                               |
| --------------------------------------- | ----------------------- | ----------------------------------------- |
| `johannschopplich.helpers.env.path`     | `kirby()->root('base')` | Directory path containing the `.env` file |
| `johannschopplich.helpers.env.filename` | `.env`                  | Environment filename to load              |

## Example: Custom Environment File

```php
// config.php - Load from a custom location
\JohannSchopplich\Helpers\Env::load('/custom/path', '.env.production');

return [
    'debug' => env('KIRBY_DEBUG', false)
];
```

## License

[MIT](../LICENSE) License © 2020-PRESENT [Johann Schopplich](https://github.com/johannschopplich)
