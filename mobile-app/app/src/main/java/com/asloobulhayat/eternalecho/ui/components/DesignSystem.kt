@file:OptIn(ExperimentalMaterial3Api::class)

package com.asloobulhayat.eternalecho.ui.components

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.RowScope
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.luminance
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import com.asloobulhayat.eternalecho.theme.DarkGreenBackground

/** Shared radii for a calm, consistent surface language. */
object EchoRadius {
    val sm = 10.dp
    val md = 14.dp
    val lg = 18.dp
    val xl = 22.dp
}

/**
 * Soft vertical wash that adapts to light / dark / signature green themes.
 */
@Composable
fun EchoScreenBackground(
    modifier: Modifier = Modifier,
    content: @Composable () -> Unit
) {
    val scheme = MaterialTheme.colorScheme
    val isDark = scheme.background.luminance() < 0.5f
    val isSignature = scheme.background == DarkGreenBackground
    val top = when {
        isSignature -> Color(0xFF0D221D)
        isDark -> scheme.background
        else -> scheme.background
    }
    val bottom = when {
        isSignature -> Color(0xFF071210)
        isDark -> scheme.surface
        else -> scheme.primaryContainer.copy(alpha = 0.18f)
    }

    Box(
        modifier = modifier
            .fillMaxSize()
            .background(Brush.verticalGradient(listOf(top, bottom)))
    ) {
        content()
    }
}

/**
 * Unified top bar — surface-tinted, not loud primaryContainer blocks.
 */
@Composable
fun EchoTopBar(
    title: String,
    onBackClick: (() -> Unit)? = null,
    navigationIcon: ImageVector? = null,
    actions: @Composable RowScope.() -> Unit = {}
) {
    val scheme = MaterialTheme.colorScheme
    TopAppBar(
        title = {
            Text(
                text = title,
                fontWeight = FontWeight.Bold,
                style = MaterialTheme.typography.titleLarge,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
        },
        navigationIcon = {
            if (onBackClick != null && navigationIcon != null) {
                IconButton(onClick = onBackClick) {
                    Icon(navigationIcon, contentDescription = "Back")
                }
            }
        },
        actions = actions,
        colors = TopAppBarDefaults.topAppBarColors(
            containerColor = scheme.surface.copy(alpha = 0.92f),
            titleContentColor = scheme.onSurface,
            navigationIconContentColor = scheme.onSurface,
            actionIconContentColor = scheme.onSurfaceVariant
        )
    )
}

@Composable
fun EchoCard(
    modifier: Modifier = Modifier,
    onClick: (() -> Unit)? = null,
    highlighted: Boolean = false,
    contentPadding: PaddingValues = PaddingValues(16.dp),
    content: @Composable ColumnScope.() -> Unit
) {
    val scheme = MaterialTheme.colorScheme
    val border = if (highlighted) {
        BorderStroke(1.5.dp, scheme.primary)
    } else {
        BorderStroke(1.dp, scheme.outlineVariant.copy(alpha = 0.7f))
    }
    val container = if (highlighted) {
        scheme.primaryContainer.copy(alpha = 0.45f)
    } else {
        scheme.surface.copy(alpha = 0.92f)
    }

    val cardContent: @Composable ColumnScope.() -> Unit = {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(contentPadding),
            content = content
        )
    }

    if (onClick != null) {
        Card(
            onClick = onClick,
            modifier = modifier.fillMaxWidth(),
            shape = RoundedCornerShape(EchoRadius.lg),
            colors = CardDefaults.cardColors(containerColor = container),
            border = border,
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
            content = cardContent
        )
    } else {
        Card(
            modifier = modifier.fillMaxWidth(),
            shape = RoundedCornerShape(EchoRadius.lg),
            colors = CardDefaults.cardColors(containerColor = container),
            border = border,
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
            content = cardContent
        )
    }
}

@Composable
fun EchoPrimaryButton(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    height: Dp = 52.dp
) {
    Button(
        onClick = onClick,
        enabled = enabled,
        modifier = modifier
            .fillMaxWidth()
            .height(height),
        shape = RoundedCornerShape(EchoRadius.md),
        colors = ButtonDefaults.buttonColors(
            containerColor = MaterialTheme.colorScheme.primary,
            contentColor = MaterialTheme.colorScheme.onPrimary
        )
    ) {
        Text(text = text, fontWeight = FontWeight.SemiBold)
    }
}

@Composable
fun EchoSecondaryButton(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    height: Dp = 52.dp
) {
    OutlinedButton(
        onClick = onClick,
        enabled = enabled,
        modifier = modifier
            .fillMaxWidth()
            .height(height),
        shape = RoundedCornerShape(EchoRadius.md),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant)
    ) {
        Text(text = text, fontWeight = FontWeight.SemiBold)
    }
}

@Composable
fun EchoChip(
    label: String,
    selected: Boolean,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    val scheme = MaterialTheme.colorScheme
    Surface(
        onClick = onClick,
        shape = RoundedCornerShape(20.dp),
        color = if (selected) scheme.primary else scheme.surfaceVariant.copy(alpha = 0.55f),
        border = BorderStroke(
            1.dp,
            if (selected) scheme.primary else scheme.outlineVariant.copy(alpha = 0.6f)
        ),
        modifier = modifier
    ) {
        Text(
            text = label,
            style = MaterialTheme.typography.labelMedium,
            fontWeight = if (selected) FontWeight.Bold else FontWeight.Medium,
            color = if (selected) scheme.onPrimary else scheme.onSurfaceVariant,
            modifier = Modifier.padding(horizontal = 14.dp, vertical = 8.dp)
        )
    }
}

@Composable
fun EchoSectionLabel(
    text: String,
    modifier: Modifier = Modifier
) {
    Text(
        text = text,
        style = MaterialTheme.typography.labelMedium,
        fontWeight = FontWeight.Bold,
        color = MaterialTheme.colorScheme.primary,
        modifier = modifier.padding(bottom = 6.dp)
    )
}
