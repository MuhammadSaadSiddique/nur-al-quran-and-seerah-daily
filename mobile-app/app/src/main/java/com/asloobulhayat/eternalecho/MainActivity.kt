package com.asloobulhayat.eternalecho

import android.content.Intent
import android.net.Uri
import android.os.Build
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import com.asloobulhayat.eternalecho.notifications.EternalEchoMessagingService
import com.asloobulhayat.eternalecho.notifications.NotificationHelper
import com.asloobulhayat.eternalecho.notifications.NotificationRepository
import com.asloobulhayat.eternalecho.notifications.NotificationSyncWorker
import com.asloobulhayat.eternalecho.security.AppSecurity
import com.asloobulhayat.eternalecho.theme.EternalEchoMobileTheme
import com.asloobulhayat.eternalecho.util.DeepLinkDestination
import com.asloobulhayat.eternalecho.util.DeepLinkHelper
import androidx.compose.ui.ExperimentalComposeUiApi
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.semantics.testTagsAsResourceId

class MainActivity : ComponentActivity() {

  private var currentDeepLink by mutableStateOf<DeepLinkDestination?>(null)

  private val requestPermissionLauncher = registerForActivityResult(
    ActivityResultContracts.RequestPermission()
  ) { isGranted: Boolean ->
    if (isGranted) {
      EternalEchoMessagingService.registerCurrentFcmToken(this)
      NotificationSyncWorker.triggerImmediateSync(this)
    }
  }

  override fun onCreate(savedInstanceState: Bundle?) {
    super.onCreate(savedInstanceState)

    // Allow user for screenshot and screen recording across the app
    AppSecurity.disableScreenSecurity(this)

    // Parse deep link or notification tap extras from launch intent
    handleIncomingIntent(intent)

    // Initialize notification channels, FCM token registration & background sync worker
    NotificationHelper.createNotificationChannel(this)
    EternalEchoMessagingService.registerCurrentFcmToken(this)
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
      EternalEchoMobileTheme {
        Surface(
          modifier = Modifier
            .fillMaxSize()
            .semantics {
              @OptIn(ExperimentalComposeUiApi::class)
              testTagsAsResourceId = true
            },
          color = MaterialTheme.colorScheme.background
        ) {
          MainNavigation(
            deepLinkDestination = currentDeepLink,
            onDeepLinkHandled = { currentDeepLink = null }
          )
        }
      }
    }
  }

  override fun onNewIntent(intent: Intent) {
    super.onNewIntent(intent)
    setIntent(intent)
    handleIncomingIntent(intent)
  }

  private fun handleIncomingIntent(intent: Intent?) {
    if (intent == null) return

    // Mark notification as read if opened from system notification or FCM data payload
    val notificationId = intent.getLongExtra("EXTRA_NOTIFICATION_ID", -1L).takeIf { it > 0 }
      ?: intent.getStringExtra("notification_id")?.toLongOrNull()
    if (notificationId != null && notificationId > 0) {
      NotificationRepository.getInstance(this).markAsRead(notificationId)
    }

    // Parse deep link from intent URI or notification action URL
    val actionUrl = intent.getStringExtra("EXTRA_ACTION_URL")
      ?: intent.getStringExtra("action_url")
    val targetUri = intent.data ?: actionUrl?.takeIf { it.isNotBlank() }?.let { Uri.parse(it) }
    targetUri?.let { uri ->
      DeepLinkHelper.parse(uri)?.let { destination ->
        currentDeepLink = destination
      }
    }
  }
}
