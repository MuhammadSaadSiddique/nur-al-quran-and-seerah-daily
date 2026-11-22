package com.asloobulhayat.eternalecho.util

import android.content.ActivityNotFoundException
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Build

/**
 * Opens a web page in the phone's browser even when it is on our own domain.
 *
 * The app is a verified App Link handler for every path on [DeepLinkHelper.WEB_HOST], so a plain
 * ACTION_VIEW for a page with no in-app screen (e.g. /donate) would resolve straight back to the app.
 * Targeting the browser's package directly avoids that. Needs the https `<queries>` entry in the manifest.
 */
object BrowserHelper {

    fun open(context: Context, url: String): Boolean {
        val view = Intent(Intent.ACTION_VIEW, Uri.parse(url))
            .addCategory(Intent.CATEGORY_BROWSABLE)
            .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)

        browserPackage(context)?.let { pkg ->
            try {
                context.startActivity(Intent(view).setPackage(pkg))
                return true
            } catch (_: ActivityNotFoundException) {
            } catch (_: SecurityException) {
            }
        }

        // No browser visible to us: let the system pick its browser app via a selector.
        return try {
            context.startActivity(Intent(view).apply {
                selector = Intent.makeMainSelectorActivity(Intent.ACTION_MAIN, Intent.CATEGORY_APP_BROWSER)
            })
            true
        } catch (_: Exception) {
            false
        }
    }

    /** The default browser, or the first installed one when no default is set. Never this app. */
    private fun browserPackage(context: Context): String? {
        val pm = context.packageManager
        val probe = Intent(Intent.ACTION_VIEW, Uri.parse("https://example.com"))
            .addCategory(Intent.CATEGORY_BROWSABLE)
        val own = context.packageName

        val default = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            pm.resolveActivity(probe, PackageManager.ResolveInfoFlags.of(PackageManager.MATCH_DEFAULT_ONLY.toLong()))
        } else {
            @Suppress("DEPRECATION")
            pm.resolveActivity(probe, PackageManager.MATCH_DEFAULT_ONLY)
        }?.activityInfo?.packageName

        // "android" means the system chooser, i.e. no default browser is set.
        if (default != null && default != "android" && default != own) return default

        val all = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            pm.queryIntentActivities(probe, PackageManager.ResolveInfoFlags.of(0L))
        } else {
            @Suppress("DEPRECATION")
            pm.queryIntentActivities(probe, 0)
        }
        return all.map { it.activityInfo.packageName }.firstOrNull { it != own }
    }
}
