# Configuring two-factor authentication

You can configure two-factor authentication using a mobile app.
A time-based one-time password (TOTP) application automatically generates an authentication code that changes after a certain period of time.

**Tip**: To configure authentication via TOTP on multiple devices, during setup, scan the QR code using each device at the same time.
If 2FA is already enabled and you want to add another device, you must re-configure 2FA from your security settings.

## Enabling two-factor authentication

### 1. Download an authenticator app

Any authenticator app should work with Friendica.
Nonetheless, we recommend:

 - For iOS, [Matt Rubin's MIT-licensed Authenticator app](https://mattrubin.me/authenticator).
 - For Android, [andOTP](https://github.com/andOTP/andOTP).

### 2. Record your one-use recovery codes

From your [two-factor authentication user settings](/settings/2fa), enter your password and click on "Enable two-factor authentication".

You will be presented with a list of one-use recovery codes.
Please save those in the same place you are saving your Friendica password (ideally, in a password manager like [KeePass](https://keepass.info)).

When you're done, click on "Next".

### 3. Setup your authenticator app

You have three methods to setup your authenticator app:

1. Scan the QR Code with your device camera.
   This will automatically configure your account on the app.
2. Click/tap on the provided **totp://** URl.
   Ideally your authenticator app should be called with this URL and set up your account.
3. Enter your account settings manually.
   Friendica is using default settings for token type, code digit count and hashing algorithm but you may be required to enter them in your app.

**Tip**: If you have multiple devices, configure them all at this point.

Then verify your app is correctly configured by submitting a code provided by your app.
This will conclude two-factor authentication configuration.

**Note:** If you leave this screen at any point without having submitted a verification code, two-factor authentication won't be enabled on your account.
To complete the configuration, just come back to your [two-factor authentication user settings](/settings/2fa) and click on "Finish configuration" after entering your current password.

## Disabling two-factor authentication

You can disable two-factor authentication at any time by going to your [two-factor authentication user settings](/settings/2fa) and click on "Disable two-factor authentication" after entering your current password.

You should remove your Friendica account from your authenticator app as it won't work again even if you reenable two-factor authentication.
In this case you will have to configure your authenticator app again using the process above.

## Managing your one-time recovery codes

When two-factor authentication is enabled, you can show your recovery codes, including the ones you've already used.

You can freely regenerate a new set of fresh recovery codes, just be sure to replace the previous ones where you saved them as they won't be active anymore.

## Third-party applications and API

Third-party applications using the Friendica API can't accept two-factor time-based authentication codes.
Instead, if you enabled two-factor authentication, you have to generate app-specific randomly generated long passwords to use in your apps instead of your regular account password.

**Note**: Your regular password won't work at all when prompted in third-party apps if you enabled two-factor authentication.

You can generate as many app-specific passwords as you want, they will be shown once to you just after you generated it.
Just copy and paste it in your third-party app in the Friendica account password input field at this point.
We recommend generating a single app-specific password for each separate third-party app you are using, using a meaningful description of the target app (like "Frienqa on my Fairphone 2").

You can also revoke any and all app-specific password you generated this way.
This may log you out of the third-party application(s) you used the revoked app-specific password to log in with.

## Trusted browsers

As a convenience, during two-factor authentication it is possible to identify a browser as trusted.
This will skip all further two-factor authentication prompt on this browser.

You can remove any or all of these trusted browsers in the two-factor authentication settings.
