package com.asloobulhayat.eternalecho.ui.adaptive

import android.app.Activity
import android.graphics.Rect
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.window.layout.FoldingFeature
import androidx.window.layout.WindowInfoTracker
import androidx.window.layout.WindowLayoutInfo
import kotlinx.coroutines.flow.flowOf

enum class WindowWidthClass {
    Compact,   // < 600dp (standard portrait phones, folded book foldables)
    Medium,    // 600dp - 839dp (phones in landscape, small tablets, unfolded book foldables)
    Expanded   // >= 840dp (large tablets, desktop, wide screens)
}

enum class WindowHeightClass {
    Compact,   // < 480dp (phones in landscape)
    Medium,    // 480dp - 899dp (standard phones in portrait)
    Expanded   // >= 900dp (very tall flip phones unfolded, tablets in portrait)
}

sealed interface Posture {
    data object Normal : Posture
    data class TableTop(val hingeBounds: Rect, val isSeparating: Boolean) : Posture
    data class Book(val hingeBounds: Rect, val isSeparating: Boolean) : Posture
}

sealed interface PaneMode {
    data object SinglePane : PaneMode
    data class DualPaneVertical(val hingeBounds: Rect? = null) : PaneMode
    data class DualPaneHorizontal(val hingeBounds: Rect? = null) : PaneMode
}

data class WindowAdaptiveInfo(
    val widthClass: WindowWidthClass,
    val heightClass: WindowHeightClass,
    val posture: Posture,
    val paneMode: PaneMode,
    val isLandscape: Boolean,
    val screenWidthDp: Dp,
    val screenHeightDp: Dp
) {
    val isDualPane: Boolean
        get() = paneMode is PaneMode.DualPaneVertical || paneMode is PaneMode.DualPaneHorizontal

    val isTableTop: Boolean
        get() = posture is Posture.TableTop

    val isBook: Boolean
        get() = posture is Posture.Book

    val shouldUseNavigationRail: Boolean
        get() = (widthClass != WindowWidthClass.Compact || isLandscape) && !isTableTop
}

/**
 * Pure calculation function for adaptive configuration, fully unit-testable without Android runtime.
 */
fun calculateAdaptiveInfo(
    screenWidthDp: Dp,
    screenHeightDp: Dp,
    foldingFeature: FoldingFeature? = null
): WindowAdaptiveInfo {
    val widthClass = when {
        screenWidthDp < 600.dp -> WindowWidthClass.Compact
        screenWidthDp < 840.dp -> WindowWidthClass.Medium
        else -> WindowWidthClass.Expanded
    }

    val heightClass = when {
        screenHeightDp < 480.dp -> WindowHeightClass.Compact
        screenHeightDp < 900.dp -> WindowHeightClass.Medium
        else -> WindowHeightClass.Expanded
    }

    val posture = when {
        foldingFeature != null && foldingFeature.state == FoldingFeature.State.HALF_OPENED -> {
            if (foldingFeature.orientation == FoldingFeature.Orientation.HORIZONTAL) {
                Posture.TableTop(foldingFeature.bounds, foldingFeature.isSeparating)
            } else {
                Posture.Book(foldingFeature.bounds, foldingFeature.isSeparating)
            }
        }
        foldingFeature != null && foldingFeature.isSeparating -> {
            if (foldingFeature.orientation == FoldingFeature.Orientation.HORIZONTAL) {
                Posture.TableTop(foldingFeature.bounds, isSeparating = true)
            } else {
                Posture.Book(foldingFeature.bounds, isSeparating = true)
            }
        }
        else -> Posture.Normal
    }

    val paneMode = when (posture) {
        is Posture.TableTop -> PaneMode.DualPaneHorizontal(posture.hingeBounds)
        is Posture.Book -> PaneMode.DualPaneVertical(posture.hingeBounds)
        is Posture.Normal -> {
            if (widthClass != WindowWidthClass.Compact) {
                PaneMode.DualPaneVertical(null)
            } else {
                PaneMode.SinglePane
            }
        }
    }

    val isLandscape = screenWidthDp > screenHeightDp

    return WindowAdaptiveInfo(
        widthClass = widthClass,
        heightClass = heightClass,
        posture = posture,
        paneMode = paneMode,
        isLandscape = isLandscape,
        screenWidthDp = screenWidthDp,
        screenHeightDp = screenHeightDp
    )
}

@Composable
fun rememberWindowAdaptiveInfo(): WindowAdaptiveInfo {
    val configuration = LocalConfiguration.current
    val screenWidthDp = configuration.screenWidthDp.dp
    val screenHeightDp = configuration.screenHeightDp.dp

    val activity = LocalContext.current as? Activity

    val windowLayoutInfo by remember(activity) {
        if (activity != null) {
            WindowInfoTracker.getOrCreate(activity).windowLayoutInfo(activity)
        } else {
            flowOf(WindowLayoutInfo(emptyList()))
        }
    }.collectAsStateWithLifecycle(initialValue = WindowLayoutInfo(emptyList()))

    val foldingFeature = remember(windowLayoutInfo) {
        windowLayoutInfo.displayFeatures
            .filterIsInstance<FoldingFeature>()
            .firstOrNull()
    }

    return remember(screenWidthDp, screenHeightDp, foldingFeature) {
        calculateAdaptiveInfo(
            screenWidthDp = screenWidthDp,
            screenHeightDp = screenHeightDp,
            foldingFeature = foldingFeature
        )
    }
}
