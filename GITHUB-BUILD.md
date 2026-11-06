# GitHub APK Build Instructions

This package includes a GitHub Actions workflow to compile a test APK without Android Studio.

1. Confirm the OneSignal App ID is configured for Android with Firebase Cloud Messaging.
2. Replace `REPLACE_WITH_ANDROID_ONESIGNAL_APP_ID` in `android-app/app/build.gradle.kts` with the confirmed Android App ID.
3. Create a new GitHub repository and upload all package contents to its root, including the hidden `.github` folder.
4. Commit to the `main` branch.
5. Open the repository's Actions tab and run `Build Prophet Precious Global APK`.
6. When successful, download the `prophet-precious-global-debug-apk` artifact. Extract it to get `app-debug.apk`.

Do not commit the OneSignal REST API key or signing passwords. This workflow creates a debug APK for testing only. A signed release APK and Google Play AAB require a secure release keystore and signing configuration.
