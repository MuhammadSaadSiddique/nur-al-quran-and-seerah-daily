package com.asloobulhayat.eternalecho.util

import android.Manifest
import android.annotation.SuppressLint
import android.content.Context
import android.content.pm.PackageManager
import android.location.Geocoder
import android.location.Location
import android.location.LocationManager
import android.os.Build
import android.os.CancellationSignal
import androidx.core.content.ContextCompat
import androidx.core.location.LocationManagerCompat
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.suspendCancellableCoroutine
import kotlinx.coroutines.withContext
import kotlinx.coroutines.withTimeoutOrNull
import java.util.Locale
import kotlin.coroutines.resume

/** Readable place details for a coordinate, used to label the screen and prefill forms. */
data class PlaceInfo(
    val label: String,
    val locality: String,
    val city: String,
    val country: String,
    val countryCode: String,
    val addressLine: String
)

/**
 * Device location without Google Play services: the platform LocationManager,
 * so it works on any Android phone the app supports.
 */
object LocationHelper {
    val PERMISSIONS = arrayOf(
        Manifest.permission.ACCESS_FINE_LOCATION,
        Manifest.permission.ACCESS_COARSE_LOCATION
    )

    fun hasPermission(context: Context): Boolean =
        granted(context, Manifest.permission.ACCESS_FINE_LOCATION) ||
            granted(context, Manifest.permission.ACCESS_COARSE_LOCATION)

    fun hasPrecisePermission(context: Context): Boolean =
        granted(context, Manifest.permission.ACCESS_FINE_LOCATION)

    fun isLocationEnabled(context: Context): Boolean {
        val lm = context.getSystemService(Context.LOCATION_SERVICE) as? LocationManager ?: return false
        return LocationManagerCompat.isLocationEnabled(lm)
    }

    /**
     * Best available fix. A recent cached fix is reused unless [requireFresh],
     * which adding a mosque uses so its pin lands on the building.
     */
    @SuppressLint("MissingPermission")
    suspend fun currentLocation(
        context: Context,
        requireFresh: Boolean = false,
        timeoutMs: Long = 15_000
    ): Location? {
        if (!hasPermission(context)) return null
        val lm = context.getSystemService(Context.LOCATION_SERVICE) as? LocationManager ?: return null

        val providers = buildList {
            if (hasPrecisePermission(context)) add(LocationManager.GPS_PROVIDER)
            add(LocationManager.NETWORK_PROVIDER)
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) add(LocationManager.FUSED_PROVIDER)
        }.filter { provider ->
            try {
                lm.isProviderEnabled(provider)
            } catch (_: Exception) {
                false
            }
        }
        if (providers.isEmpty()) return null

        val lastKnown = providers.mapNotNull { provider ->
            try {
                lm.getLastKnownLocation(provider)
            } catch (_: SecurityException) {
                null
            }
        }.maxByOrNull { it.time }

        val maxAgeMs = if (requireFresh) 30_000L else 2 * 60_000L
        if (lastKnown != null &&
            System.currentTimeMillis() - lastKnown.time < maxAgeMs &&
            (!requireFresh || lastKnown.accuracy <= 50f)
        ) {
            return lastKnown
        }

        val preferred = if (requireFresh && LocationManager.GPS_PROVIDER in providers) {
            LocationManager.GPS_PROVIDER
        } else {
            providers.firstOrNull { it != LocationManager.GPS_PROVIDER } ?: providers.first()
        }

        val fresh = withTimeoutOrNull(timeoutMs) {
            suspendCancellableCoroutine<Location?> { cont ->
                val signal = CancellationSignal()
                cont.invokeOnCancellation { signal.cancel() }
                try {
                    LocationManagerCompat.getCurrentLocation(
                        lm,
                        preferred,
                        signal,
                        ContextCompat.getMainExecutor(context)
                    ) { location ->
                        if (cont.isActive) cont.resume(location)
                    }
                } catch (_: Exception) {
                    if (cont.isActive) cont.resume(null)
                }
            }
        }

        return fresh ?: lastKnown
    }

    /** Reverse-geocode to an area name; null when no geocoder or no network. */
    suspend fun describe(context: Context, latitude: Double, longitude: Double): PlaceInfo? =
        withContext(Dispatchers.IO) {
            if (!Geocoder.isPresent()) return@withContext null
            try {
                @Suppress("DEPRECATION")
                val address = Geocoder(context, Locale.getDefault())
                    .getFromLocation(latitude, longitude, 1)
                    ?.firstOrNull() ?: return@withContext null

                val locality = address.subLocality ?: address.thoroughfare ?: ""
                val city = address.locality ?: address.subAdminArea ?: address.adminArea ?: ""
                val label = listOf(locality, city).filter { it.isNotBlank() }.distinct().joinToString(", ")
                PlaceInfo(
                    label = label.ifBlank { address.countryName.orEmpty() },
                    locality = locality,
                    city = city,
                    country = address.countryName.orEmpty(),
                    countryCode = address.countryCode.orEmpty(),
                    addressLine = address.getAddressLine(0).orEmpty()
                )
            } catch (_: Exception) {
                null
            }
        }

    private fun granted(context: Context, permission: String): Boolean =
        ContextCompat.checkSelfPermission(context, permission) == PackageManager.PERMISSION_GRANTED
}
