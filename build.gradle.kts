plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
}

android {
    namespace = "com.profetional.global"
    compileSdk = 35

    defaultConfig {
        applicationId = "com.profetional.global"
        minSdk = 23
        targetSdk = 35
        versionCode = 1
        versionName = "1.0.0"
        buildConfigField("String", "ONESIGNAL_APP_ID", "\"REPLACE_WITH_ANDROID_ONESIGNAL_APP_ID\"")
    }

    buildFeatures { buildConfig = true }
}

dependencies {
    implementation("androidx.core:core-ktx:1.15.0")
    implementation("androidx.appcompat:appcompat:1.7.0")
    implementation("com.onesignal:OneSignal:5.1.28")
}
