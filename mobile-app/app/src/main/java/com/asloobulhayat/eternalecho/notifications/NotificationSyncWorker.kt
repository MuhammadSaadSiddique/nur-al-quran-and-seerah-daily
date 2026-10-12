package com.asloobulhayat.eternalecho.notifications

import android.content.Context
import androidx.work.*
import java.util.concurrent.TimeUnit

class NotificationSyncWorker(
    private val appContext: Context,
    workerParams: WorkerParameters
) : CoroutineWorker(appContext, workerParams) {

    override suspend fun doWork(): Result {
        return try {
            val repository = NotificationRepository.getInstance(appContext)
            repository.syncNotifications(showAlerts = true)
            Result.success()
        } catch (e: Exception) {
            android.util.Log.e("NotificationSyncWorker", "Background sync failed: ${e.message}", e)
            Result.retry()
        }
    }

    companion object {
        const val WORK_NAME = "eternal_echo_notification_sync"

        fun schedulePeriodicSync(context: Context) {
            val constraints = Constraints.Builder()
                .setRequiredNetworkType(NetworkType.CONNECTED)
                .build()

            val syncRequest = PeriodicWorkRequestBuilder<NotificationSyncWorker>(
                15, TimeUnit.MINUTES
            )
                .setConstraints(constraints)
                .setBackoffCriteria(BackoffPolicy.EXPONENTIAL, 1, TimeUnit.MINUTES)
                .build()

            WorkManager.getInstance(context).enqueueUniquePeriodicWork(
                WORK_NAME,
                ExistingPeriodicWorkPolicy.KEEP,
                syncRequest
            )
        }

        fun triggerImmediateSync(context: Context) {
            val constraints = Constraints.Builder()
                .setRequiredNetworkType(NetworkType.CONNECTED)
                .build()

            val oneTimeRequest = OneTimeWorkRequestBuilder<NotificationSyncWorker>()
                .setConstraints(constraints)
                .build()

            WorkManager.getInstance(context).enqueue(oneTimeRequest)
        }
    }
}
