# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | ✅ Active support  |

## Reporting a Vulnerability

**⚠️ DO NOT create a public GitHub issue for security vulnerabilities.**

If you discover a security vulnerability within CryptoGateway, please send an email to:

📧 **security@botdigit.com**

Include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

### Response Timeline

- **Acknowledgment:** Within 48 hours
- **Initial assessment:** Within 1 week
- **Fix & release:** Depending on severity (critical: 24-72 hours)

### What Qualifies as a Security Vulnerability

- Private key exposure or leakage
- Webhook signature bypass
- SQL injection in package queries
- Authentication/authorization bypass
- Encryption weaknesses in KeyEncryptor
- Rate limiting bypass
- Remote code execution

### What Does NOT Qualify

- Denial of service via high request volume (use infrastructure-level protection)
- Issues in dependencies (report to the upstream project)
- Missing features or enhancements

## Security Best Practices for Users

1. **Always set `CRYPTO_WEBHOOK_SECRET`** — never run webhooks without signature verification
2. **Use `encrypt_keys = true`** (default) — never store raw private keys
3. **Keep `.env` out of version control** — never commit API keys
4. **Use testnet first** — always test with testnet before mainnet
5. **Rate limit your endpoints** — configure `security.rate_limit` appropriately
6. **Monitor your logs** — review `cryptogateway` log channel regularly
7. **Keep the package updated** — `composer update botdigit/cryptogateway`

## Hall of Fame

We thank the following individuals for responsibly disclosing security issues:

*(No entries yet — be the first!)*
