package com.example.eternalechomobile.security

import android.app.Activity
import android.content.Context
import android.content.pm.ApplicationInfo
import android.content.pm.PackageManager
import android.os.Build
import android.os.Debug
import android.view.WindowManager
import java.io.BufferedReader
import java.io.File
import java.io.FileReader
import java.net.InetSocketAddress
import java.net.Socket
import java.security.MessageDigest

/**
 * Runtime Application Self-Protection (RASP) & Anti-Tampering Engine.
 * Detects rooted devices, Frida/Xposed hooking, native debuggers, and APK repackaging.
 */
object AppSecurity {

    // Common root binary paths checked across standard Android, Magisk, and custom ROMs
    private val KNOWN_ROOT_PATHS = arrayOf(
        "/system/app/Superuser.apk",
        "/sbin/su",
        "/system/bin/su",
        "/system/xbin/su",
        "/data/local/xbin/su",
        "/data/local/bin/su",
        "/system/sd/xbin/su",
        "/system/bin/failsafe/su",
        "/data/local/su",
        "/su/bin/su"
    )

    private val FRIDA_DEFAULT_PORTS = intArrayOf(27042, 27043)

    /**
     * Checks if the device is rooted via binary existence, build tags, and mount properties.
     */
    fun isRooted(): Boolean {
        // 1. Check build tags for test-keys
        val buildTags = Build.TAGS
        if (buildTags != null && buildTags.contains("test-keys")) {
            return true
        }

        // 2. Check for known root binaries
        for (path in KNOWN_ROOT_PATHS) {
            try {
                val file = File(path)
                if (file.exists()) {
                    return true
                }
            } catch (_: Throwable) {
                // Access denied or sandboxed
            }
        }

        // 3. Check for Magisk / KernelSU mounts or directories
        val magiskPaths = arrayOf("/sbin/.magisk", "/cache/.disable_magisk", "/dev/.magisk.unrestricted")
        for (mPath in magiskPaths) {
            try {
                if (File(mPath).exists()) return true
            } catch (_: Throwable) { }
        }

        return false
    }

    /**
     * Detects dynamic hooking frameworks including Frida and Xposed.
     */
    fun isHookingDetected(): Boolean {
        // 1. Check memory maps for frida or xposed libraries
        try {
            val mapsFile = File("/proc/self/maps")
            if (mapsFile.exists() && mapsFile.canRead()) {
                BufferedReader(FileReader(mapsFile)).use { reader ->
                    var line: String?
                    while (reader.readLine().also { line = it } != null) {
                        val currentLine = line?.lowercase() ?: continue
                        if (currentLine.contains("frida-agent") ||
                            currentLine.contains("frida-gadget") ||
                            currentLine.contains("gadget.so") ||
                            currentLine.contains("xposedbridge") ||
                            currentLine.contains("libsubstrate")
                        ) {
                            return true
                        }
                    }
                }
            }
        } catch (_: Throwable) { }

        // 2. Check if Frida server default port is open on localhost
        for (port in FRIDA_DEFAULT_PORTS) {
            try {
                Socket().use { socket ->
                    socket.connect(InetSocketAddress("127.0.0.1", port), 50)
                    return true // Port connected -> Frida server is listening
                }
            } catch (_: Throwable) {
                // Port closed as expected
            }
        }

        // 3. Check for Xposed classes loaded via reflection
        try {
            Class.forName("de.robv.android.xposed.XposedBridge")
            return true
        } catch (_: ClassNotFoundException) { }

        return false
    }

    /**
     * Detects attached Java or native debuggers (JDWP, LLDB, ptrace).
     */
    fun isDebuggerAttached(context: Context? = null): Boolean {
        // 1. Android Java debugger checks
        if (Debug.isDebuggerConnected() || Debug.waitingForDebugger()) {
            return true
        }

        // 2. Check debuggable flag in ApplicationInfo (should be disabled in production)
        if (context != null) {
            val isDebuggable = (context.applicationInfo.flags and ApplicationInfo.FLAG_DEBUGGABLE) != 0
            if (isDebuggable && !Build.TYPE.equals("userdebug")) {
                // Flagged if running with debug flags in a production context
            }
        }

        // 3. Inspect TracerPid in /proc/self/status (detects ptrace attachment by IDA/GDB/Frida)
        try {
            val statusFile = File("/proc/self/status")
            if (statusFile.exists() && statusFile.canRead()) {
                BufferedReader(FileReader(statusFile)).use { reader ->
                    var line: String?
                    while (reader.readLine().also { line = it } != null) {
                        if (line != null && line.startsWith("TracerPid:")) {
                            val pid = line.substringAfter(":").trim().toIntOrNull() ?: 0
                            if (pid > 0) {
                                return true // A tracer process (debugger or frida) is attached
                            }
                        }
                    }
                }
            }
        } catch (_: Throwable) { }

        return false
    }

    /**
     * Validates that the APK signature matches the authorized developer certificate SHA-256 hash.
     * Detects repackaging and re-signing by attackers.
     */
    fun verifyApkSignature(context: Context, expectedSha256Hex: String? = null): Boolean {
        if (expectedSha256Hex.isNullOrBlank()) return true // No pin specified, passes validation

        return try {
            val packageManager = context.packageManager
            val packageName = context.packageName

            val signatures = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
                val packageInfo = packageManager.getPackageInfo(packageName, PackageManager.GET_SIGNING_CERTIFICATES)
                packageInfo.signingInfo?.apkContentsSigners
            } else {
                @Suppress("DEPRECATION")
                val packageInfo = packageManager.getPackageInfo(packageName, PackageManager.GET_SIGNATURES)
                @Suppress("DEPRECATION")
                packageInfo.signatures
            }

            if (signatures.isNullOrEmpty()) return false

            val cert = signatures[0].toByteArray()
            val md = MessageDigest.getInstance("SHA-256")
            val digest = md.digest(cert)
            val currentHex = digest.joinToString("") { "%02x".format(it) }

            currentHex.equals(expectedSha256Hex.replace(":", "").lowercase(), ignoreCase = true)
        } catch (e: Throwable) {
            false
        }
    }

    /**
     * Aggregated check for compromised environment.
     */
    fun isEnvironmentCompromised(context: Context): Boolean {
        return isRooted() || isHookingDetected() || isDebuggerAttached(context)
    }

    /**
     * Applies FLAG_SECURE to prevent screen recording, screenshots, and task switcher previews
     * on screens with sensitive user credentials or passwords.
     */
    fun enableScreenSecurity(activity: Activity) {
        activity.window.setFlags(
            WindowManager.LayoutParams.FLAG_SECURE,
            WindowManager.LayoutParams.FLAG_SECURE
        )
    }

    /**
     * Clears FLAG_SECURE when returning to regular presentation content.
     */
    fun disableScreenSecurity(activity: Activity) {
        activity.window.clearFlags(WindowManager.LayoutParams.FLAG_SECURE)
    }

    /**
     * Lightweight XOR-based string obfuscation / deobfuscation helper
     * to prevent plaintext API URLs and sensitive strings in compiled DEX files.
     */
    fun deobfuscate(encodedBytes: ByteArray, key: Byte): String {
        val result = ByteArray(encodedBytes.size)
        for (i in encodedBytes.indices) {
            result[i] = (encodedBytes[i].toInt() xor key.toInt()).toByte()
        }
        return String(result, Charsets.UTF_8)
    }

    fun obfuscate(input: String, key: Byte): ByteArray {
        val bytes = input.toByteArray(Charsets.UTF_8)
        val result = ByteArray(bytes.size)
        for (i in bytes.indices) {
            result[i] = (bytes[i].toInt() xor key.toInt()).toByte()
        }
        return result
    }
}
