package com.asloobulhayat.eternalecho.ui.home

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeOut
import androidx.compose.animation.shrinkVertically
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.HowToVote
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.asloobulhayat.eternalecho.data.SurveyStore
import com.asloobulhayat.eternalecho.ui.components.PressableScale
import com.asloobulhayat.eternalecho.util.BrowserHelper
import com.asloobulhayat.eternalecho.util.DeepLinkHelper

private const val SURVEY_WEB_URL = "${DeepLinkHelper.BASE_WEB_URL}/survey?from=android"

/** Dark ink for text on the gold pill: about 11:1 on the dark themes' amber, 6:1 on the light theme's. */
private val OnGold = Color(0xFF1C1300)

/**
 * Home prompt for the "Help shape Eternal Echo" survey. Opens the in-app survey via [onOpen]
 * (or the web survey in the browser when no handler is given), says "Continue" once started,
 * and disappears after the survey is sent or the close button is tapped.
 */
@Composable
fun SurveyHomeCard(palette: HomeThemePalette, modifier: Modifier = Modifier, onOpen: (() -> Unit)? = null) {
    val context = LocalContext.current
    var hidden by remember { mutableStateOf(SurveyStore.isDismissed(context) || SurveyStore.isDone(context)) }
    val opened = remember { SurveyStore.isOpened(context) }

    AnimatedVisibility(
        visible = !hidden,
        exit = fadeOut() + shrinkVertically(),
        modifier = modifier
    ) {
        Column {
            Box {
                PressableScale(
                    onClick = {
                        if (onOpen != null) {
                            onOpen()
                        } else if (BrowserHelper.open(context, SURVEY_WEB_URL)) {
                            SurveyStore.markOpened(context)
                        }
                    },
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Card(
                        shape = RoundedCornerShape(18.dp),
                        colors = CardDefaults.cardColors(containerColor = palette.cardBg),
                        border = BorderStroke(1.dp, palette.goldAccent.copy(alpha = 0.45f)),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Box(
                            modifier = Modifier.background(
                                Brush.horizontalGradient(
                                    listOf(palette.goldAccent.copy(alpha = 0.14f), palette.goldAccent.copy(alpha = 0.02f))
                                )
                            )
                        ) {
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                modifier = Modifier.padding(start = 14.dp, top = 14.dp, bottom = 14.dp, end = 40.dp)
                            ) {
                                Box(
                                    contentAlignment = Alignment.Center,
                                    modifier = Modifier
                                        .size(44.dp)
                                        .clip(CircleShape)
                                        .background(palette.goldAccent.copy(alpha = 0.18f))
                                ) {
                                    Icon(Icons.Default.HowToVote, contentDescription = null, tint = palette.goldAccent)
                                }
                                Column(
                                    verticalArrangement = Arrangement.spacedBy(2.dp),
                                    modifier = Modifier
                                        .weight(1f)
                                        .padding(horizontal = 12.dp)
                                ) {
                                    Text(
                                        if (opened) "Finish shaping Eternal Echo" else "Help shape Eternal Echo",
                                        color = palette.textPrimary,
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 15.sp
                                    )
                                    Text(
                                        "2-minute survey: tell us what you need and vote on what we build next",
                                        color = palette.textSecondary,
                                        fontSize = 12.sp,
                                        lineHeight = 16.sp
                                    )
                                    Spacer(Modifier.height(6.dp))
                                    Box(
                                        modifier = Modifier
                                            .clip(RoundedCornerShape(50))
                                            .background(palette.goldAccent)
                                            .padding(horizontal = 12.dp, vertical = 5.dp)
                                    ) {
                                        Text(
                                            if (opened) "Continue →" else "Take the survey →",
                                            color = OnGold,
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 12.sp
                                        )
                                    }
                                }
                            }
                        }
                    }
                }

                // A sibling above the card, so tapping it never also opens the survey.
                IconButton(
                    onClick = {
                        hidden = true
                        SurveyStore.dismiss(context)
                    },
                    modifier = Modifier
                        .align(Alignment.TopEnd)
                        .padding(2.dp)
                ) {
                    Icon(Icons.Default.Close, contentDescription = "Hide survey card", tint = palette.textMuted, modifier = Modifier.size(18.dp))
                }
            }
            Spacer(Modifier.height(14.dp))
        }
    }
}
