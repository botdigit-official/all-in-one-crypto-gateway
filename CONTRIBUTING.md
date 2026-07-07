# Contributing to CryptoGateway

Thank you for considering contributing to CryptoGateway! This document provides guidelines and instructions for contributing.

## 🐛 Bug Reports

Before creating a bug report, please check existing issues to avoid duplicates.

When creating a bug report, include:
- **PHP and Laravel versions**
- **Package version** (`composer show botdigit/cryptogateway`)
- **Steps to reproduce** the issue
- **Expected behavior** vs. **actual behavior**
- **Error logs** (sanitize any sensitive data like API keys)

## 💡 Feature Requests

Open an issue with the `[Feature Request]` prefix. Describe:
- **What** you want to add
- **Why** it would be useful
- **How** it could work (if you have ideas)

## 🔧 Development Setup

### 1. Fork & Clone

```bash
git clone https://github.com/YOUR_USERNAME/cryptogateway.git
cd cryptogateway
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Run Tests

```bash
./vendor/bin/phpunit
```

### 4. Code Style

We follow **PSR-12** coding standards. Run the style checker before submitting:

```bash
./vendor/bin/phpcs --standard=PSR12 src/
```

## 📝 Pull Request Process

1. **Fork** the repository and create a feature branch:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Write tests** for your changes. PRs without tests may not be merged.

3. **Follow existing patterns.** Look at existing drivers, DTOs, and adapters for structure.

4. **Update documentation** if your change affects the public API.

5. **Commit with clear messages:**
   ```
   feat: add Dogecoin driver support
   fix: correct ETH gas estimation for ERC-20 transfers
   docs: update custom driver documentation
   ```

6. **Push and create a PR** against the `main` branch.

## 🪙 Adding a New Coin Driver

This is the most common contribution. Here's how:

### Step 1: Create the Adapter (if needed)

If your coin uses a unique protocol (not JSON-RPC or EVM), create an adapter in `src/Adapters/`:

```php
namespace Botdigit\CryptoGateway\Adapters;

class YourCoinAdapter
{
    // Handle raw RPC/HTTP communication
}
```

### Step 2: Create the Driver

Create your driver in `src/Drivers/`:

```php
namespace Botdigit\CryptoGateway\Drivers;

class YourCoinDriver extends AbstractDriver
{
    // Implement all DriverInterface methods
}
```

### Step 3: Register in GatewayManager

Add your driver to the `$builtInDrivers` array in `src/GatewayManager.php`.

### Step 4: Add Default Config

Add a default config entry in `config/cryptogateway.php`.

### Step 5: Add Address Validation

Add regex patterns in `src/Security/AddressValidator.php`.

### Step 6: Write Tests

Create tests in `tests/Unit/Drivers/YourCoinDriverTest.php`.

### Step 7: Update Documentation

Update `README.md` with the new coin.

## 📜 Code of Conduct

- Be respectful and inclusive
- Provide constructive feedback
- Focus on the code, not the person
- Help newcomers get started

## 📄 License

By contributing, you agree that your contributions will be licensed under the MIT License.
