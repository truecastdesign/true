# WebAuthn / FIDO2

This directory is a vendored copy of [lbuchs/webauthn](https://github.com/lbuchs/webauthn)
v2.2.0 (last upstream commit September 2025).

The source has not been modified. The original namespace `lbuchs\WebAuthn`
is preserved so upstream documentation, examples, and patches apply directly.

## Why it's here

To remove the Composer dependency on `lbuchs/webauthn` and ship WebAuthn
support inside the `truecastdesign/true` framework itself. Frozen here, not
forked — pulling in a future upstream release is a `diff -r` away rather
than a port.

## License

MIT, copyright Lukas Buchs. See [LICENSE](LICENSE).

## Updating

To pull a newer upstream version:

```sh
git clone --depth=1 --branch <tag> https://github.com/lbuchs/webauthn /tmp/webauthn-upstream
rsync -a --delete /tmp/webauthn-upstream/src/ vendor/truecastdesign/true/src/WebAuthn/
cp /tmp/webauthn-upstream/LICENSE vendor/truecastdesign/true/src/WebAuthn/LICENSE
```

Then re-run `composer dump-autoload`.
