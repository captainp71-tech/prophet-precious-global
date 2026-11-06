# Prophet Precious Global Android App Starter

Branding:
- App name: Prophet Precious Global
- Tagline: Understanding the Hidden. Changing the Outcome.
- Package ID: `com.profetional.global`
- Brand colours: violet, white, and red
- Website: https://prophetprecious.com
- App homepage: https://prophetprecious.com/app-home/

## What is included

1. A WordPress plugin that provides the `[ppg_app_home]` shortcode, an Intervention-category story feed, and a WordPress admin notification screen.
2. An Android Studio Kotlin project using a native Android WebView and OneSignal SDK.
3. A JavaScript bridge that maps a logged-in WordPress user to a OneSignal external ID in the form `wp_USERID`.

## Important: this is source code, not a finished APK

This package has not been compiled or tested against your live WordPress site, payment flows, or a physical Android device. It does not contain an APK or Google Play AAB. Build and test the Android project in Android Studio before distribution.

## WordPress installation

1. In WordPress Admin, go to Plugins > Add New > Upload Plugin.
2. Upload `wordpress-plugin/prophet-precious-app-bridge.zip` and activate it.
3. Create a page titled `App Home`, with slug `app-home`.
4. Put `[ppg_app_home]` in the page content.
5. Confirm the category slug is exactly `intervention`. Posts in that category populate the latest stories section automatically.
6. In the new `PPG Android App` menu, add the Android OneSignal App ID and REST API Key.
7. Use the `Push Notifications` submenu to send a test notification.

The homepage currently links to `/general-diagnosis`, `/register-to-the-prayer-altar/`, and `/partner-dashboard/`. Confirm these pages are live and that their slugs are correct on the production site.

## OneSignal setup

The OneSignal App ID supplied during planning was `7438acf8-a757-460c-b4c1-a2470fb64c99`. Do not assume it is correctly configured for Android. In OneSignal, confirm that the app has the Android platform configured and that Firebase Cloud Messaging credentials are connected. If that ID belongs only to the old web/PWA setup, create or configure the correct Android app and replace the placeholder in `app/build.gradle.kts`.

The OneSignal REST API Key belongs only in the WordPress plugin settings. Never put it in Android source code, JavaScript, or a public repository.

The plugin sends broadcast notifications with an Android platform tag filter. The Android app adds the `platform=android` tag on login. Selected-member notifications use external IDs `wp_USERID`.

## Android build

1. Open the `android-app` folder in Android Studio.
2. Replace `REPLACE_WITH_ANDROID_ONESIGNAL_APP_ID` in `app/build.gradle.kts` with the confirmed OneSignal Android App ID.
3. Allow Gradle sync and install the required Android SDK 35.
4. Build > Generate Signed Bundle / APK.
5. Generate an APK for direct website distribution and an AAB for Google Play.
6. Keep the signing keystore and passwords backed up securely. Use the same signing identity for future direct APK releases.

## Before launch: required testing

- WordPress login, logout, cookies, and member dashboard.
- Prayer Altar registration and member-specific pages.
- Payment gateway redirects and return URLs.
- File uploads, downloads, and document links.
- Notification permission prompt and notification tap/deep link.
- Android back navigation and external links.
- Confirm only Android subscribers receive the Android broadcast.
- Verify the WordPress REST API and app homepage over HTTPS.

## Known starter limitations

- The current notification manager supports all Android subscribers and selected WordPress user IDs. Group targeting by Watchman or Altar membership needs a reliable membership-to-OneSignal tag sync before it should be enabled.
- Push notification links currently open through the OneSignal URL handler. A full deep-link routing layer should be tested and added before launch.
- The WordPress plugin does not change your existing homepage. It creates a separate shortcode-based app homepage.
- The app does not replace WordPress accounts. It uses the existing website login.
- The privacy policy and terms URLs supplied during planning should be checked on the live site before Play Store submission.
- This starter does not upload an APK to your website or publish to Google Play automatically.
