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
-repackageclasses 'com.example.eternalechomobile.sec'
-dontusemixedcaseclassnames

# --- Remove Source File & Debug Metadata ---
# Strips original Kotlin file names and line mappings so decompilers see blank origins
-renamesourcefileattribute ""
-keepattributes *Annotation*,Signature,InnerClasses,EnclosingMethod

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
-keep,allowobfuscation class com.example.eternalechomobile.data.** { *; }

# --- Android Components & Jetpack Compose ---
-keep public class com.example.eternalechomobile.MainActivity { *; }

# Preserve Compose Runtime
-keepclassmembers class * {
    @androidx.compose.runtime.Composable *;
}
-dontwarn androidx.compose.**
-dontwarn kotlinx.coroutines.**
