# Contributing to WP Djot

Contributions are welcome! Please feel free to submit a Pull Request.

## How to Contribute

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Development Setup

### Requirements

- PHP 8.2 or higher
- Composer
- WordPress 6.0+ (for testing)
- WP-CLI (optional, for CLI command testing)

### Installation

```bash
git clone https://github.com/php-collective/wp-djot.git
cd wp-djot
composer install
```

### Running Tests

```bash
composer test
```

### Code Style

This project follows PSR-12 coding standards.

```bash
# Check code style
composer cs-check

# Fix code style automatically
composer cs-fix
```

### Static Analysis

```bash
composer stan
```

## Pull Request Guidelines

- Follow the existing code style
- Add tests for new features
- Update documentation as needed
- Keep commits focused and atomic
- Write clear commit messages

## Reporting Issues

- Use the GitHub issue tracker
- Include WordPress and PHP version
- Provide steps to reproduce the issue
- Include any error messages

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
