package com.asloobulhayat.eternalecho.data

import kotlinx.coroutines.flow.StateFlow

/**
 * Compatibility façade over [DailyEngagementStore] for nested screens.
 */
object EngagementBus {
    val refreshTick: StateFlow<Int> get() = DailyEngagementStore.syncTick
    val latest: StateFlow<DailyEngagement?> get() = DailyEngagementStore.engagement

    fun publish(engagement: DailyEngagement) {
        DailyEngagementStore.publish(engagement)
    }

    fun requestRefresh() {
        DailyEngagementStore.requestSync()
    }

    fun markLocal(activityKey: String): DailyEngagement {
        return DailyEngagementStore.markLocal(activityKey)
    }
}
