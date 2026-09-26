package com.example.eternalechomobile

import com.example.eternalechomobile.data.ApiClient
import com.example.eternalechomobile.security.AppSecurity
import com.example.eternalechomobile.security.CryptoEngine
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotEquals
import org.junit.Assert.assertTrue
import org.junit.Assert.fail
import org.junit.Test
import javax.crypto.AEADBadTagException

class SecurityTest {

    @Test
    fun testCryptoEngineEncryptDecryptSymmetry() {
        val testInputs = listOf(
            "simple_secret_token_12345",
            "بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ",
            "user_id=42&name=Ahmad&email=ahmad@example.com",
            "{\"token\":\"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9\"}"
        )

        for (input in testInputs) {
            val encrypted = CryptoEngine.encrypt(input)
            assertNotEquals("Ciphertext must not match plaintext", input, encrypted)
            assertTrue("Encrypted output must not be empty", encrypted.isNotEmpty())

            val decrypted = CryptoEngine.decrypt(encrypted)
            assertEquals("Decrypted output must match original input", input, decrypted)
        }
    }

    @Test
    fun testCryptoEngineProducesUniqueIVs() {
        val plainText = "same_credential_value"
        val cipher1 = CryptoEngine.encrypt(plainText)
        val cipher2 = CryptoEngine.encrypt(plainText)

        assertNotEquals(
            "AES-GCM must generate unique initialization vectors (IV) for every encryption",
            cipher1,
            cipher2
        )

        // Both must decrypt back to identical plaintext
        assertEquals(plainText, CryptoEngine.decrypt(cipher1))
        assertEquals(plainText, CryptoEngine.decrypt(cipher2))
    }

    @Test
    fun testCryptoEngineRejectsTamperedCiphertext() {
        val plainText = "tamper_proof_sensitive_payload"
        val encrypted = CryptoEngine.encrypt(plainText)

        // Tamper with the ciphertext by flipping bytes
        val bytes = java.util.Base64.getDecoder().decode(encrypted)
        bytes[bytes.size - 1] = (bytes[bytes.size - 1].toInt() xor 0xFF).toByte()
        val tamperedEncrypted = java.util.Base64.getEncoder().encodeToString(bytes)

        try {
            CryptoEngine.decrypt(tamperedEncrypted)
            fail("Decryption of tampered AES-GCM ciphertext must fail authentication")
        } catch (_: AEADBadTagException) {
            // Expected: GCM tag validation failed
        } catch (_: Exception) {
            // Also acceptable: generic cipher exception
        }
    }

    @Test
    fun testStringObfuscationAndBaseUrlDeobfuscation() {
        val expectedUrl = "https://theeternalecho.asloobulhayat.com/api/v1/mobile.php"
        assertEquals(
            "Deobfuscated ApiClient BASE_URL must match authorized HTTPS endpoint",
            expectedUrl,
            ApiClient.BASE_URL
        )

        val customSecret = "MyCustomSecretKey"
        val key: Byte = 0x3C
        val obfuscated = AppSecurity.obfuscate(customSecret, key)
        val recovered = AppSecurity.deobfuscate(obfuscated, key)
        assertEquals(customSecret, recovered)
    }

    @Test
    fun testRaspChecksExecuteSafelyWithoutCrashing() {
        // Verify RASP checks execute cleanly without throwing unhandled exceptions
        val isRooted = AppSecurity.isRooted()
        val isHooking = AppSecurity.isHookingDetected()
        val isDebugger = AppSecurity.isDebuggerAttached()

        // Standard JVM test environment should not be detected as mobile-rooted
        assertFalse("JVM unit test host should not detect Android root binaries", isRooted)
        assertFalse("JVM unit test host should not detect mobile Frida hooking agent", isHooking)
    }

    @Test
    fun testApkSignatureValidation() {
        // Without expected fingerprint provided, returns true by default
        val stubContext = object : android.content.ContextWrapper(null) {}
        assertTrue(AppSecurity.verifyApkSignature(stubContext, null))
    }
}
