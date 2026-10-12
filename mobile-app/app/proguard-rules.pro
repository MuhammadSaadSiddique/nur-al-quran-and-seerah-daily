# ==============================================================================
# ETERNAL ECHO - ADVANCED IRREVERSIBLE OBFUSCATION & HARDENING PROGUARD RULES
# ==============================================================================

# --- Aggressive Optimization & Code Shrinking ---
-optimizationpasses 5
-allowaccessmodification
-mergeinterfacesaggressively
-overloadaggressively

# --- Obfuscation & Package Structure Destruction ---
# Repackage all classes into a single obscured flat namespace, destroying all package architecture
-repackageclasses 'com.asloobulhayat.eternalecho.sec'
-dontusemixedcaseclassnames

# --- Source File & Debug Metadata for Crashlytics ---
-renamesourcefileattribute SourceFile
-keepattributes *Annotation*,Signature,InnerClasses,EnclosingMethod,SourceFile,LineNumberTable

# --- Firebase & Google Services ---
-keep class com.google.firebase.** { *; }
-dontwarn com.google.firebase.**
-keep class com.google.android.gms.** { *; }
-dontwarn com.google.android.gms.**

# --- WorkManager & Background Workers ---
-keep class androidx.work.** { *; }
-keep class * extends androidx.work.ListenableWorker {
    public <init>(android.content.Context, androidx.work.WorkerParameters);
}
-keep class com.asloobulhayat.eternalecho.notifications.** { *; }

# --- Strip All Android Log Statements Completely ---
# Ensures no error messages, URLs, or debugging information ever leak via logcat
-assumenosideeffects class android.util.Log {
    public static boolean isLoggable(java.lang.String, int);
    public static int v(...);
    public static int d(...);
    public static int i(...);
    public static int w(...);
    public static int e(...);
}

# --- Kotlinx Serialization & Data Models ---
# Retain only required serialization companion methods for network payloads
-keepattributes *Annotation*
-keepclassmembers class * {
    @kotlinx.serialization.SerialName <fields>;
}
-keepclassmembers class * {
    *** Companion;
}
-keepclasseswithmembers class * {
    kotlinx.serialization.KSerializer serializer(...);
}
-keep,allowobfuscation class com.asloobulhayat.eternalecho.data.** { *; }

# --- Android Components & Jetpack Compose ---
-keep public class com.asloobulhayat.eternalecho.MainActivity { *; }

# Preserve Compose Runtime
-keepclassmembers class * {
    @androidx.compose.runtime.Composable *;
}
-dontwarn androidx.compose.**
-dontwarn kotlinx.coroutines.**
