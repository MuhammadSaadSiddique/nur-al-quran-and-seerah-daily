package com.asloobulhayat.eternalecho

import android.os.Build
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import com.asloobulhayat.eternalecho.notifications.NotificationHelper
import com.asloobulhayat.eternalecho.notifications.NotificationSyncWorker
import com.asloobulhayat.eternalecho.security.AppSecurity
import com.asloobulhayat.eternalecho.theme.EternalEchoMobileTheme

class MainActivity : ComponentActivity() {

  private val requestPermissionLauncher = registerForActivityResult(
    ActivityResultContracts.RequestPermission()
  ) { isGranted: Boolean ->
    if (isGranted) {
      NotificationSyncWorker.triggerImmediateSync(this)
    }
  }

  override fun onCreate(savedInstanceState: Bundle?) {
    super.onCreate(savedInstanceState)

    // Anti-Screen Scraping & Tapjacking protection for sensitive user data
    AppSecurity.enableScreenSecurity(this)
      /*val launchIntent = intent
      if (launchIntent.action == "com.google.intent.action.TEST_LOOP") {
          val scenario = launchIntent.getIntExtra("scenario", 0)
          // Code to handle your game loop here
      }*/
    // Initialize notification channels & background sync worker
    NotificationHelper.createNotificationChannel(this)
    NotificationSyncWorker.schedulePeriodicSync(this)

    // Request notification permission on Android 13+ (API 33+)
    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
      if (!NotificationHelper.hasNotificationPermission(this)) {
        requestPermissionLauncher.launch(android.Manifest.permission.POST_NOTIFICATIONS)
      } else {
        NotificationSyncWorker.triggerImmediateSync(this)
      }
    } else {
      NotificationSyncWorker.triggerImmediateSync(this)
    }

    enableEdgeToEdge()
    setContent {
      EternalEchoMobileTheme { Surface(modifier = Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) { MainNavigation() } }
    }
  }
}
