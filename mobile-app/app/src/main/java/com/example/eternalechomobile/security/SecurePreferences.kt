package com.asloobulhayat.eternalecho.security

import android.content.Context
import android.content.SharedPreferences

/**
 * Encrypted preferences wrapper protecting user session tokens, IDs, and quiz scores
 * from physical extraction, rooted inspection, and ADB backup attacks.
 */
class SecurePreferences private constructor(context: Context) {

    private val prefs: SharedPreferences = context.applicationContext.getSharedPreferences(
        PREFS_NAME,
        Context.MODE_PRIVATE
    )

    fun putString(key: String, value: String?) {
        val encryptedKey = hashKey(key)
        if (value == null) {
            prefs.edit().remove(encryptedKey).remove(key).apply()
            return
        }
        val encryptedValue = CryptoEngine.encrypt(value)
        prefs.edit()
            .putString(encryptedKey, encryptedValue)
            .remove(key) // Clean up legacy plaintext key if any
            .apply()
    }

    fun getString(key: String, defaultValue: String?): String? {
        val encryptedKey = hashKey(key)
        val encryptedValue = prefs.getString(encryptedKey, null)
        if (encryptedValue != null) {
            return try {
                CryptoEngine.decrypt(encryptedValue)
            } catch (e: Throwable) {
                defaultValue
            }
        }
        // Fallback for reading existing plaintext keys during migration
        val legacyValue = prefs.getString(key, null)
        if (legacyValue != null) {
            // Transparently migrate to encrypted key
            putString(key, legacyValue)
            return legacyValue
        }
        return defaultValue
    }

    fun putInt(key: String, value: Int) {
        putString(key, value.toString())
    }

    fun getInt(key: String, defaultValue: Int): Int {
        val str = getString(key, null) ?: return prefs.getInt(key, defaultValue)
        return str.toIntOrNull() ?: defaultValue
    }

    fun putLong(key: String, value: Long) {
        putString(key, value.toString())
    }

    fun getLong(key: String, defaultValue: Long): Long {
        val str = getString(key, null) ?: return try { prefs.getLong(key, defaultValue) } catch (_: Exception) { defaultValue }
        return str.toLongOrNull() ?: defaultValue
    }


    fun putBoolean(key: String, value: Boolean) {
        putString(key, value.toString())
    }

    fun getBoolean(key: String, defaultValue: Boolean): Boolean {
        val str = getString(key, null) ?: return prefs.getBoolean(key, defaultValue)
        return str.toBooleanStrictOrNull() ?: defaultValue
    }

    fun remove(key: String) {
        val encryptedKey = hashKey(key)
        prefs.edit().remove(encryptedKey).remove(key).apply()
    }

    fun clear() {
        prefs.edit().clear().apply()
    }

    private fun hashKey(key: String): String {
        // Obfuscate preferences keys so attackers scanning XML files cannot identify what data is stored
        val digest = java.security.MessageDigest.getInstance("SHA-256")
        val hash = digest.digest(key.toByteArray(Charsets.UTF_8))
        return hash.joinToString("") { "%02x".format(it) }.take(16)
    }

    companion object {
        private const val PREFS_NAME = "eternal_echo_secure_vault"

        @Volatile
        private var instance: SecurePreferences? = null

        fun getInstance(context: Context): SecurePreferences {
            return instance ?: synchronized(this) {
                instance ?: SecurePreferences(context).also { instance = it }
            }
        }
    }
}
