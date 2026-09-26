package com.example.eternalechomobile.ui.adaptive

import android.graphics.Rect
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.VerticalDivider
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.unit.dp

@Composable
fun AdaptiveTwoPane(
    adaptiveInfo: WindowAdaptiveInfo,
    firstPane: @Composable BoxScope.() -> Unit,
    secondPane: @Composable BoxScope.() -> Unit,
    modifier: Modifier = Modifier,
    firstPaneWeight: Float = 0.42f,
    secondPaneWeight: Float = 0.58f,
    singlePaneContent: (@Composable BoxScope.() -> Unit)? = null
) {
    Box(modifier = modifier.fillMaxSize()) {
        when (adaptiveInfo.paneMode) {
            is PaneMode.DualPaneHorizontal -> {
                // Flip phone / Clamshell TableTop (Flex) Mode: Top & Bottom split
                Column(modifier = Modifier.fillMaxSize()) {
                    Box(
                        modifier = Modifier
                            .weight(1f)
                            .fillMaxWidth()
                    ) {
                        firstPane()
                    }

                    // Hinge crease separation
                    HingeHorizontalSpacer(hingeBounds = adaptiveInfo.paneMode.hingeBounds)

                    Box(
                        modifier = Modifier
                            .weight(1f)
                            .fillMaxWidth()
                    ) {
                        secondPane()
                    }
                }
            }

            is PaneMode.DualPaneVertical -> {
                // Wide phone, tablet, landscape, or Book posture: Side-by-Side split
                Row(modifier = Modifier.fillMaxSize()) {
                    Box(
                        modifier = Modifier
                            .weight(firstPaneWeight)
                            .fillMaxHeight()
                    ) {
                        firstPane()
                    }

                    // Hinge / pane divider
                    HingeVerticalSpacer(hingeBounds = adaptiveInfo.paneMode.hingeBounds)

                    Box(
                        modifier = Modifier
                            .weight(secondPaneWeight)
                            .fillMaxHeight()
                    ) {
                        secondPane()
                    }
                }
            }

            is PaneMode.SinglePane -> {
                if (singlePaneContent != null) {
                    singlePaneContent()
                } else {
                    firstPane()
                }
            }
        }
    }
}

@Composable
fun HingeHorizontalSpacer(
    hingeBounds: Rect?,
    modifier: Modifier = Modifier
) {
    val density = LocalDensity.current
    val hingeHeight = if (hingeBounds != null && hingeBounds.height() > 0) {
        with(density) { hingeBounds.height().toDp() }
    } else {
        12.dp
    }

    Box(
        modifier = modifier
            .fillMaxWidth()
            .height(hingeHeight)
            .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f)),
        contentAlignment = androidx.compose.ui.Alignment.Center
    ) {
        HorizontalDivider(
            color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.6f),
            thickness = 1.dp
        )
    }
}

@Composable
fun HingeVerticalSpacer(
    hingeBounds: Rect?,
    modifier: Modifier = Modifier
) {
    val density = LocalDensity.current
    val hingeWidth = if (hingeBounds != null && hingeBounds.width() > 0) {
        with(density) { hingeBounds.width().toDp() }
    } else {
        1.dp
    }

    Box(
        modifier = modifier
            .fillMaxHeight()
            .width(hingeWidth)
            .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f)),
        contentAlignment = androidx.compose.ui.Alignment.Center
    ) {
        VerticalDivider(
            color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.6f),
            thickness = 1.dp
        )
    }
}
