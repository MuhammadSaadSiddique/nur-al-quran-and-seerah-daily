package com.asloobulhayat.eternalecho

import android.graphics.Rect
import androidx.compose.ui.unit.dp
import androidx.window.layout.FoldingFeature
import com.asloobulhayat.eternalecho.ui.adaptive.PaneMode
import com.asloobulhayat.eternalecho.ui.adaptive.Posture
import com.asloobulhayat.eternalecho.ui.adaptive.WindowHeightClass
import com.asloobulhayat.eternalecho.ui.adaptive.WindowWidthClass
import com.asloobulhayat.eternalecho.ui.adaptive.calculateAdaptiveInfo
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class AdaptiveLayoutTest {

    private class MockFoldingFeature(
        override val bounds: Rect = Rect(),
        override val state: FoldingFeature.State = FoldingFeature.State.HALF_OPENED,
        override val orientation: FoldingFeature.Orientation = FoldingFeature.Orientation.HORIZONTAL,
        override val isSeparating: Boolean = true,
        override val occlusionType: FoldingFeature.OcclusionType = FoldingFeature.OcclusionType.NONE
    ) : FoldingFeature

    @Test
    fun testCompactPortraitPhoneDefaultsToSinglePaneAndBottomNav() {
        val info = calculateAdaptiveInfo(
            screenWidthDp = 390.dp,
            screenHeightDp = 844.dp,
            foldingFeature = null
        )

        assertEquals(WindowWidthClass.Compact, info.widthClass)
        assertEquals(WindowHeightClass.Medium, info.heightClass)
        assertEquals(Posture.Normal, info.posture)
        assertEquals(PaneMode.SinglePane, info.paneMode)
        assertFalse("Portrait phone must not be landscape", info.isLandscape)
        assertFalse("Standard portrait phone must use single pane", info.isDualPane)
        assertFalse("Standard phone is not tabletop", info.isTableTop)
        assertFalse("Standard phone is not book", info.isBook)
        assertFalse("Compact portrait must use bottom navigation bar, not navigation rail", info.shouldUseNavigationRail)
    }

    @Test
    fun testLandscapePhoneTransitionsToDualPaneAndNavigationRail() {
        val info = calculateAdaptiveInfo(
            screenWidthDp = 844.dp,
            screenHeightDp = 390.dp,
            foldingFeature = null
        )

        assertEquals(WindowWidthClass.Expanded, info.widthClass)
        assertEquals(WindowHeightClass.Compact, info.heightClass)
        assertEquals(Posture.Normal, info.posture)
        assertTrue("Rotated phone should be in DualPaneVertical", info.paneMode is PaneMode.DualPaneVertical)
        assertTrue("844dp width is landscape", info.isLandscape)
        assertTrue("Landscape phone should enable dual pane", info.isDualPane)
        assertTrue("Landscape phone should switch to navigation rail to save vertical height", info.shouldUseNavigationRail)
    }

    @Test
    fun testMediumLandscapePhoneTransitionsToNavigationRailAndDualPane() {
        val info = calculateAdaptiveInfo(
            screenWidthDp = 720.dp,
            screenHeightDp = 360.dp,
            foldingFeature = null
        )

        assertEquals(WindowWidthClass.Medium, info.widthClass)
        assertEquals(WindowHeightClass.Compact, info.heightClass)
        assertTrue("Medium landscape phone should be DualPaneVertical", info.paneMode is PaneMode.DualPaneVertical)
        assertTrue(info.isLandscape)
        assertTrue(info.isDualPane)
        assertTrue("Medium landscape phone must use navigation rail", info.shouldUseNavigationRail)
    }

    @Test
    fun testLargeTabletShowsDualPaneAndNavigationRail() {
        val info = calculateAdaptiveInfo(
            screenWidthDp = 1024.dp,
            screenHeightDp = 1366.dp,
            foldingFeature = null
        )

        assertEquals(WindowWidthClass.Expanded, info.widthClass)
        assertEquals(WindowHeightClass.Expanded, info.heightClass)
        assertEquals(Posture.Normal, info.posture)
        assertTrue(info.paneMode is PaneMode.DualPaneVertical)
        assertTrue("Tablet should use dual pane", info.isDualPane)
        assertTrue("Tablet should use navigation rail", info.shouldUseNavigationRail)
    }

    @Test
    fun testClamshellFlipPhoneTableTopMode() {
        val feature = MockFoldingFeature(
            state = FoldingFeature.State.HALF_OPENED,
            orientation = FoldingFeature.Orientation.HORIZONTAL
        )

        val info = calculateAdaptiveInfo(
            screenWidthDp = 400.dp,
            screenHeightDp = 800.dp,
            foldingFeature = feature
        )

        assertTrue("Flip phone partially folded horizontally must be TableTop posture", info.posture is Posture.TableTop)
        assertTrue("TableTop posture must produce DualPaneHorizontal pane mode", info.paneMode is PaneMode.DualPaneHorizontal)
        assertTrue(info.isTableTop)
        assertTrue(info.isDualPane)
        assertFalse("TableTop mode should keep bottom navigation / control area instead of rail", info.shouldUseNavigationRail)
    }

    @Test
    fun testBookFoldablePosture() {
        val feature = MockFoldingFeature(
            state = FoldingFeature.State.HALF_OPENED,
            orientation = FoldingFeature.Orientation.VERTICAL
        )

        val info = calculateAdaptiveInfo(
            screenWidthDp = 700.dp,
            screenHeightDp = 800.dp,
            foldingFeature = feature
        )

        assertTrue("Book foldable partially folded vertically must be Book posture", info.posture is Posture.Book)
        assertTrue("Book posture must produce DualPaneVertical pane mode", info.paneMode is PaneMode.DualPaneVertical)
        assertTrue(info.isBook)
        assertTrue(info.isDualPane)
        assertTrue("Book foldable should use navigation rail", info.shouldUseNavigationRail)
    }
}
