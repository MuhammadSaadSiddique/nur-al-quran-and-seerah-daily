package com.asloobulhayat.eternalecho.ui.survey

import android.app.Application
import android.content.Intent
import androidx.activity.compose.BackHandler
import androidx.compose.animation.AnimatedContent
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.SizeTransform
import androidx.compose.animation.animateColorAsState
import androidx.compose.animation.core.Animatable
import androidx.compose.animation.core.LinearEasing
import androidx.compose.animation.core.RepeatMode
import androidx.compose.animation.core.Spring
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.infiniteRepeatable
import androidx.compose.animation.core.rememberInfiniteTransition
import androidx.compose.animation.core.spring
import androidx.compose.animation.core.tween
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.scaleIn
import androidx.compose.animation.slideInHorizontally
import androidx.compose.animation.slideInVertically
import androidx.compose.animation.slideOutHorizontally
import androidx.compose.animation.slideOutVertically
import androidx.compose.animation.togetherWith
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.gestures.detectDragGestures
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.aspectRatio
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.selection.selectable
import androidx.compose.foundation.selection.toggleable
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.OpenInNew
import androidx.compose.material.icons.automirrored.filled.Undo
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Switch
import androidx.compose.material3.SwitchDefaults
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.key
import androidx.compose.runtime.mutableFloatStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.runtime.withFrameNanos
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.rotate
import androidx.compose.ui.draw.scale
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.PathEffect
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.drawscope.rotate
import androidx.compose.ui.graphics.drawscope.translate
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.hapticfeedback.HapticFeedbackType
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.platform.LocalHapticFeedback
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.heading
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.SpanStyle
import androidx.compose.ui.text.buildAnnotatedString
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardCapitalization
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextDirection
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.text.withStyle
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.zIndex
import androidx.lifecycle.viewmodel.compose.viewModel
import com.asloobulhayat.eternalecho.R
import com.asloobulhayat.eternalecho.data.SurveyFeature
import com.asloobulhayat.eternalecho.data.SurveyOption
import com.asloobulhayat.eternalecho.data.SurveyQuestion
import com.asloobulhayat.eternalecho.data.SurveyReaction
import com.asloobulhayat.eternalecho.theme.ArabicVerseFont
import com.asloobulhayat.eternalecho.ui.home.HomeThemePalette
import com.asloobulhayat.eternalecho.ui.home.rememberHomeThemePalette
import com.asloobulhayat.eternalecho.util.DeepLinkHelper
import kotlinx.coroutines.launch
import kotlin.math.abs
import kotlin.math.cos
import kotlin.math.roundToInt
import kotlin.math.sin
import kotlin.random.Random

/** Dark ink for text on gold: about 11:1 on the dark themes' amber, 6:1 on the light theme's. */
private val OnGold = Color(0xFF1C1300)

/** Header tints for the feature cards, in deck order. */
private val CardTints = listOf(
    Color(0xFF10B981), Color(0xFF0EA5E9), Color(0xFFF59E0B), Color(0xFFF43F5E),
    Color(0xFFF97316), Color(0xFF84CC16), Color(0xFFEAB308), Color(0xFF8B5CF6)
)

/**
 * "Help shape Eternal Echo": a one-question-per-screen survey that also walks people through the
 * features already in the app. Questions come from the server, so they match the website.
 */
@Composable
fun SurveyScreen(
    onBack: () -> Unit,
    onOpenFeature: (SurveyFeature) -> Unit,
    modifier: Modifier = Modifier
) {
    val app = LocalContext.current.applicationContext as Application
    val vm: SurveyViewModel = viewModel(key = "survey") { SurveyViewModel(app) }
    val state by vm.state.collectAsState()
    val palette = rememberHomeThemePalette()
    val haptics = LocalHapticFeedback.current

    BackHandler { if (!vm.back()) onBack() }

    LaunchedEffect(state.step) {
        if (state.step == SurveyStep.Done) haptics.performHapticFeedback(HapticFeedbackType.LongPress)
    }

    Box(
        modifier = modifier
            .fillMaxSize()
            .background(Brush.verticalGradient(listOf(palette.bgTop, palette.bgBottom)))
    ) {
        Column(Modifier.fillMaxSize().safeDrawingPadding()) {
            when {
                state.loading -> CenterMessage(palette) {
                    CircularProgressIndicator(color = palette.goldAccent)
                    Spacer(Modifier.height(16.dp))
                    Text("Loading the survey…", color = palette.textSecondary)
                }

                state.loadError != null -> CenterMessage(palette) {
                    Text("📡", fontSize = 48.sp)
                    Spacer(Modifier.height(12.dp))
                    Text(state.loadError.orEmpty(), color = palette.textPrimary, textAlign = TextAlign.Center)
                    Spacer(Modifier.height(20.dp))
                    GoldButton("Try again", onClick = vm::load)
                    TextButton(onClick = onBack) { Text("Go back", color = palette.textSecondary) }
                }

                else -> {
                    SurveyTopBar(state, palette, onBack = { if (!vm.back()) onBack() }, onClose = onBack)
                    Box(Modifier.weight(1f)) {
                        AnimatedContent(
                            targetState = state.index,
                            transitionSpec = {
                                val dir = if (state.forward) 1 else -1
                                (slideInHorizontally(tween(380)) { it / 5 * dir } + fadeIn(tween(300)))
                                    .togetherWith(slideOutHorizontally(tween(300)) { -it / 5 * dir } + fadeOut(tween(200)))
                                    .using(SizeTransform(clip = false))
                            },
                            label = "survey_step"
                        ) { index ->
                            StepContent(
                                step = state.steps.getOrNull(index) ?: SurveyStep.Welcome,
                                state = state,
                                vm = vm,
                                palette = palette,
                                onOpenFeature = onOpenFeature,
                                onBack = onBack
                            )
                        }
                    }
                    SurveyBottomBar(state, palette, vm)
                }
            }
        }

        SurveyToastHost(state.toast, palette)

        if (state.step == SurveyStep.Done) {
            key(state.index) { Confetti(Modifier.fillMaxSize()) }
        }
    }
}

// ---------------------------------------------------------------- chrome

@Composable
private fun SurveyTopBar(state: SurveyUiState, palette: HomeThemePalette, onBack: () -> Unit, onClose: () -> Unit) {
    Column(Modifier.fillMaxWidth().padding(horizontal = 8.dp, vertical = 4.dp)) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            if (state.inQuestions) {
                IconButton(onClick = onBack) {
                    Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Previous question", tint = palette.textSecondary)
                }
            } else {
                Image(
                    painter = painterResource(R.drawable.eternal),
                    contentDescription = null,
                    modifier = Modifier.padding(start = 12.dp, end = 4.dp).size(28.dp)
                )
            }

            Box(Modifier.weight(1f).padding(horizontal = 8.dp)) {
                if (state.inQuestions) {
                    Row(
                        horizontalArrangement = Arrangement.spacedBy(4.dp),
                        modifier = Modifier.semantics {
                            contentDescription = "Question ${state.questionNumber} of ${state.totalQuestions}"
                        }
                    ) {
                        repeat(state.totalQuestions) { i ->
                            val fill by animateFloatAsState(
                                targetValue = when {
                                    i + 1 < state.questionNumber -> 1f
                                    i + 1 == state.questionNumber -> 0.5f
                                    else -> 0f
                                },
                                animationSpec = tween(450),
                                label = "progress_$i"
                            )
                            Box(
                                Modifier
                                    .weight(1f)
                                    .height(6.dp)
                                    .clip(CircleShape)
                                    .background(palette.textMuted.copy(alpha = 0.2f))
                            ) {
                                Box(
                                    Modifier
                                        .fillMaxWidth(fill)
                                        .height(6.dp)
                                        .clip(CircleShape)
                                        .background(palette.goldAccent)
                                )
                            }
                        }
                    }
                }
            }

            XpPill(state.xp, palette)

            if (state.step != SurveyStep.Done) {
                IconButton(onClick = onClose) {
                    Icon(Icons.Default.Close, contentDescription = "Save and exit", tint = palette.textMuted)
                }
            } else {
                Spacer(Modifier.width(12.dp))
            }
        }
        if (state.inQuestions) {
            val secondsLeft = (state.totalQuestions - state.questionNumber + 1) * 12
            Row(Modifier.fillMaxWidth().padding(horizontal = 12.dp)) {
                Text(
                    "Question ${state.questionNumber} of ${state.totalQuestions}",
                    color = palette.textMuted, fontSize = 11.sp, fontWeight = FontWeight.SemiBold,
                    modifier = Modifier.weight(1f)
                )
                Text(
                    if (secondsLeft <= 20) "Almost done" else "~${maxOf(1, (secondsLeft / 60f).roundToInt())} min left",
                    color = palette.textMuted, fontSize = 11.sp, fontWeight = FontWeight.SemiBold
                )
            }
        }
    }
}

@Composable
private fun XpPill(xp: Int, palette: HomeThemePalette) {
    val bump = remember { Animatable(1f) }
    LaunchedEffect(xp) {
        if (xp > 0) {
            bump.animateTo(1.22f, tween(110))
            bump.animateTo(1f, spring(dampingRatio = Spring.DampingRatioMediumBouncy))
        }
    }
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier
            .scale(bump.value)
            .clip(CircleShape)
            .background(palette.goldAccent.copy(alpha = 0.14f))
            .border(1.dp, palette.goldAccent.copy(alpha = 0.4f), CircleShape)
            .padding(horizontal = 10.dp, vertical = 4.dp)
            .semantics { contentDescription = "$xp points" }
    ) {
        Text("✦ ", color = palette.goldAccent, fontSize = 12.sp, fontWeight = FontWeight.Black)
        Text("$xp", color = palette.goldAccent, fontSize = 12.sp, fontWeight = FontWeight.Black)
        Text(" XP", color = palette.goldAccent.copy(alpha = 0.75f), fontSize = 11.sp, fontWeight = FontWeight.Bold)
    }
}

@Composable
private fun SurveyBottomBar(state: SurveyUiState, palette: HomeThemePalette, vm: SurveyViewModel) {
    val step = state.step
    val visible = when (step) {
        is SurveyStep.Choice -> step.question.isMulti || state.answers.picked(step.question.key).isNotEmpty()
        SurveyStep.Features -> state.deckDone
        SurveyStep.Nps -> state.answers.nps != null
        SurveyStep.Wish, SurveyStep.Contact -> true
        SurveyStep.Welcome, SurveyStep.Done -> false
    }
    AnimatedVisibility(visible = visible, enter = fadeIn() + slideInVertically { it / 2 }, exit = fadeOut()) {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp),
            modifier = Modifier.fillMaxWidth().padding(horizontal = 20.dp, vertical = 12.dp)
        ) {
            if (step is SurveyStep.Choice && step.question.isMulti) {
                Text(
                    "${state.answers.picked(step.question.key).size} of ${step.question.max} picked",
                    color = palette.textMuted, fontSize = 12.sp, fontWeight = FontWeight.Bold
                )
            }
            if (step == SurveyStep.Wish && state.answers.wish.isBlank()) {
                TextButton(onClick = vm::next) { Text("Skip", color = palette.textMuted, fontWeight = FontWeight.Bold) }
            }
            val label = when {
                step == SurveyStep.Contact -> "Start my journey ✨"
                step == SurveyStep.Wish && state.answers.wish.isNotBlank() -> "Add my wish"
                else -> "Continue"
            }
            GoldButton(
                text = label,
                enabled = state.canContinue && !state.submitting,
                loading = state.submitting,
                onClick = { if (step == SurveyStep.Contact) vm.submit() else vm.next() },
                modifier = Modifier.weight(1f)
            )
        }
    }
}

@Composable
private fun GoldButton(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    loading: Boolean = false
) {
    Button(
        onClick = onClick,
        enabled = enabled,
        shape = RoundedCornerShape(16.dp),
        colors = ButtonDefaults.buttonColors(
            containerColor = Color(0xFFE6CA65),
            contentColor = OnGold,
            disabledContainerColor = Color(0x22888888),
            disabledContentColor = Color(0x99888888)
        ),
        modifier = modifier.heightIn(min = 54.dp)
    ) {
        if (loading) {
            CircularProgressIndicator(color = OnGold, strokeWidth = 2.dp, modifier = Modifier.size(18.dp))
            Spacer(Modifier.width(10.dp))
            Text("Sending…", fontWeight = FontWeight.Black, fontSize = 16.sp)
        } else {
            Text(text, fontWeight = FontWeight.Black, fontSize = 16.sp)
        }
    }
}

@Composable
private fun SurveyToastHost(toast: SurveyToast?, palette: HomeThemePalette) {
    var last by remember { mutableStateOf(toast) }
    if (toast != null) last = toast
    Box(Modifier.fillMaxWidth().padding(top = 96.dp), contentAlignment = Alignment.TopCenter) {
        AnimatedVisibility(
            visible = toast != null,
            enter = fadeIn() + slideInVertically { -it } + scaleIn(initialScale = 0.9f),
            exit = fadeOut() + slideOutVertically { -it / 2 }
        ) {
            last?.let { t ->
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier
                        .clip(CircleShape)
                        .background(palette.sheetBg)
                        .border(1.dp, palette.goldAccent.copy(alpha = 0.5f), CircleShape)
                        .padding(horizontal = 16.dp, vertical = 9.dp)
                ) {
                    Text(t.text, color = palette.textPrimary, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                    if (t.xp > 0) {
                        Spacer(Modifier.width(8.dp))
                        Text("+${t.xp} XP", color = palette.goldAccent, fontWeight = FontWeight.Black, fontSize = 13.sp)
                    }
                }
            }
        }
    }
}

@Composable
private fun CenterMessage(palette: HomeThemePalette, content: @Composable () -> Unit) {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
        modifier = Modifier.fillMaxSize().padding(32.dp)
    ) { content() }
}

// ---------------------------------------------------------------- steps

@Composable
private fun StepContent(
    step: SurveyStep,
    state: SurveyUiState,
    vm: SurveyViewModel,
    palette: HomeThemePalette,
    onOpenFeature: (SurveyFeature) -> Unit,
    onBack: () -> Unit
) {
    when (step) {
        SurveyStep.Welcome -> WelcomeStep(state, vm, palette)
        is SurveyStep.Choice -> ChoiceStep(step.question, state, vm, palette)
        SurveyStep.Features -> FeaturesStep(state, vm, palette, onOpenFeature)
        SurveyStep.Nps -> NpsStep(state, vm, palette)
        SurveyStep.Wish -> WishStep(state, vm, palette)
        SurveyStep.Contact -> ContactStep(state, vm, palette)
        SurveyStep.Done -> DoneStep(state, vm, palette, onOpenFeature, onBack)
    }
}

@Composable
private fun StepColumn(content: @Composable () -> Unit) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(horizontal = 20.dp, vertical = 16.dp)
    ) { content() }
}

@Composable
private fun StepHeader(eyebrow: String, title: String, hint: String?, palette: HomeThemePalette) {
    Text(eyebrow.uppercase(), color = palette.goldAccent, fontSize = 12.sp, fontWeight = FontWeight.Black, letterSpacing = 2.sp)
    Spacer(Modifier.height(8.dp))
    Text(
        title,
        color = palette.textPrimary,
        fontSize = 26.sp,
        lineHeight = 31.sp,
        fontWeight = FontWeight.Black,
        modifier = Modifier.semantics { heading() }
    )
    if (!hint.isNullOrBlank()) {
        Spacer(Modifier.height(6.dp))
        Text(hint, color = palette.textSecondary, fontSize = 14.sp)
    }
    Spacer(Modifier.height(20.dp))
}

@Composable
private fun WelcomeStep(state: SurveyUiState, vm: SurveyViewModel, palette: HomeThemePalette) {
    val spin = rememberInfiniteTransition(label = "welcome")
    val angle by spin.animateFloat(0f, 360f, infiniteRepeatable(tween(18_000, easing = LinearEasing)), label = "ring")
    val float by spin.animateFloat(-6f, 6f, infiniteRepeatable(tween(2500), RepeatMode.Reverse), label = "float")

    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(horizontal = 24.dp, vertical = 16.dp)
    ) {
        Box(contentAlignment = Alignment.Center, modifier = Modifier.size(140.dp).offset(y = float.dp)) {
            Canvas(Modifier.fillMaxSize().rotate(angle)) {
                drawCircle(
                    color = palette.goldAccent.copy(alpha = 0.45f),
                    style = Stroke(width = 2.dp.toPx(), pathEffect = PathEffect.dashPathEffect(floatArrayOf(10f, 10f)))
                )
            }
            Box(
                contentAlignment = Alignment.Center,
                modifier = Modifier
                    .size(108.dp)
                    .clip(CircleShape)
                    .background(Brush.linearGradient(listOf(Color(0xFFF6E58D), Color(0xFFB89428))))
                    .padding(3.dp)
                    .clip(CircleShape)
                    .background(palette.cardBg)
            ) {
                Image(painterResource(R.drawable.eternal), contentDescription = null, modifier = Modifier.size(60.dp))
            }
            Text("✨", fontSize = 20.sp, modifier = Modifier.align(Alignment.TopEnd))
            Text("🌙", fontSize = 18.sp, modifier = Modifier.align(Alignment.BottomStart))
        }

        Spacer(Modifier.height(16.dp))
        Text(
            "وَلَقَدْ يَسَّرْنَا الْقُرْآنَ لِلذِّكْرِ فَهَلْ مِن مُّدَّكِرٍ",
            fontFamily = ArabicVerseFont,
            fontSize = 26.sp,
            color = palette.goldAccent,
            style = androidx.compose.ui.text.TextStyle(textDirection = TextDirection.Rtl, textAlign = TextAlign.Center)
        )
        Text(
            "“And We have certainly made the Quran easy to remember, so is there anyone who will be mindful?” (Al-Qamar 54:17)",
            color = palette.textMuted, fontSize = 11.sp, fontStyle = FontStyle.Italic, textAlign = TextAlign.Center
        )

        Spacer(Modifier.height(20.dp))
        Text(
            buildAnnotatedString {
                append("Customize my ")
                withStyle(SpanStyle(color = palette.goldAccent)) { append("app experience") }
            },
            color = palette.textPrimary,
            fontSize = 32.sp,
            lineHeight = 36.sp,
            fontWeight = FontWeight.Black,
            textAlign = TextAlign.Center,
            modifier = Modifier.semantics { heading() }
        )
        Spacer(Modifier.height(10.dp))
        Text(
            "Tell us what you need, discover features you may have missed, and vote on what we build next.",
            color = palette.textSecondary, fontSize = 16.sp, lineHeight = 22.sp, textAlign = TextAlign.Center
        )

        Spacer(Modifier.height(20.dp))
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth()) {
            listOf("⏱️" to "About 2 min", "🗳️" to "Vote on the roadmap", "🏅" to "Earn a badge").forEach { (emoji, label) ->
                Column(
                    horizontalAlignment = Alignment.CenterHorizontally,
                    modifier = Modifier
                        .weight(1f)
                        .clip(RoundedCornerShape(16.dp))
                        .background(palette.cardBg)
                        .border(1.dp, palette.cardBorder, RoundedCornerShape(16.dp))
                        .padding(vertical = 12.dp, horizontal = 6.dp)
                ) {
                    Text(emoji, fontSize = 20.sp)
                    Spacer(Modifier.height(4.dp))
                    Text(label, color = palette.textSecondary, fontSize = 11.sp, fontWeight = FontWeight.Bold, textAlign = TextAlign.Center)
                }
            }
        }

        if (state.alreadyAnswered && state.resumeIndex == null) {
            Spacer(Modifier.height(16.dp))
            Text(
                "✅ You've already shared your voice. Thank you! Go through it again to update your answers.",
                color = palette.textPrimary,
                fontSize = 13.sp,
                fontWeight = FontWeight.SemiBold,
                modifier = Modifier
                    .fillMaxWidth()
                    .clip(RoundedCornerShape(16.dp))
                    .background(palette.primaryAccent.copy(alpha = 0.14f))
                    .padding(14.dp)
            )
        }

        Spacer(Modifier.height(24.dp))
        if (state.resumeIndex != null) {
            GoldButton("Continue where you left off →", onClick = vm::resume, modifier = Modifier.fillMaxWidth())
            TextButton(onClick = vm::restart, modifier = Modifier.fillMaxWidth()) {
                Text("Start over", color = palette.textSecondary, fontWeight = FontWeight.Bold)
            }
        } else {
            GoldButton(
                if (state.alreadyAnswered) "Update my answers" else "Let's begin",
                onClick = vm::start,
                modifier = Modifier.fillMaxWidth()
            )
        }
        Spacer(Modifier.height(8.dp))
        Text(
            if (state.signedIn) "Your answers are saved to your account." else "No sign-in needed.",
            color = palette.textMuted, fontSize = 11.sp
        )
    }
}

@Composable
private fun ChoiceStep(question: SurveyQuestion, state: SurveyUiState, vm: SurveyViewModel, palette: HomeThemePalette) {
    val haptics = LocalHapticFeedback.current
    val picked = state.answers.picked(question.key)
    val full = question.isMulti && picked.size >= question.max

    StepColumn {
        StepHeader(question.eyebrow, question.title, question.hint, palette)
        question.options.chunked(2).forEachIndexed { row, pair ->
            Row(horizontalArrangement = Arrangement.spacedBy(10.dp), modifier = Modifier.fillMaxWidth()) {
                pair.forEachIndexed { col, option ->
                    val selected = option.value in picked
                    OptionTile(
                        option = option,
                        selected = selected,
                        dimmed = full && !selected,
                        multi = question.isMulti,
                        appearDelay = (row * 2 + col) * 35,
                        shakeNonce = state.shake?.takeIf { it.first == "${question.key}:${option.value}" }?.second,
                        palette = palette,
                        onClick = {
                            haptics.performHapticFeedback(HapticFeedbackType.TextHandleMove)
                            vm.choose(question, option.value)
                        },
                        modifier = Modifier.weight(1f)
                    )
                }
                if (pair.size == 1) Spacer(Modifier.weight(1f))
            }
            Spacer(Modifier.height(10.dp))
        }
    }
}

@Composable
private fun OptionTile(
    option: SurveyOption,
    selected: Boolean,
    dimmed: Boolean,
    multi: Boolean,
    appearDelay: Int,
    shakeNonce: Long?,
    palette: HomeThemePalette,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    val appear = remember { Animatable(0f) }
    LaunchedEffect(Unit) {
        kotlinx.coroutines.delay(appearDelay.toLong())
        appear.animateTo(1f, tween(320))
    }
    val shake = remember { Animatable(0f) }
    LaunchedEffect(shakeNonce) {
        if (shakeNonce != null) {
            for (x in listOf(-10f, 10f, -8f, 8f, -4f, 0f)) shake.animateTo(x, tween(45))
        }
    }
    val pop by animateFloatAsState(
        targetValue = if (selected) 1f else 0f,
        animationSpec = spring(dampingRatio = Spring.DampingRatioMediumBouncy, stiffness = Spring.StiffnessMedium),
        label = "pop"
    )
    val border by animateColorAsState(if (selected) palette.goldAccent else palette.cardBorder, label = "border")
    val shape = RoundedCornerShape(18.dp)

    val interaction = if (multi) {
        Modifier.toggleable(value = selected, role = Role.Checkbox, onValueChange = { onClick() })
    } else {
        Modifier.selectable(selected = selected, role = Role.RadioButton, onClick = onClick)
    }

    Box(
        modifier = modifier
            .graphicsLayer {
                alpha = appear.value * (if (dimmed) 0.5f else 1f)
                translationY = (1f - appear.value) * 24f
                translationX = shake.value
            }
            .heightIn(min = 104.dp)
            .clip(shape)
            .background(if (selected) palette.goldAccent.copy(alpha = 0.14f) else palette.cardBg)
            .border(if (selected) 2.dp else 1.dp, border, shape)
            .then(interaction)
            .padding(14.dp)
    ) {
        Column {
            Box(
                contentAlignment = Alignment.Center,
                modifier = Modifier
                    .size(44.dp)
                    .scale(1f + 0.18f * pop * (1f - pop) * 4f)
                    .clip(RoundedCornerShape(12.dp))
                    .background(if (selected) palette.goldAccent.copy(alpha = 0.22f) else palette.textMuted.copy(alpha = 0.1f))
            ) {
                Text(option.emoji, fontSize = 22.sp, color = palette.textPrimary, fontWeight = FontWeight.Bold)
            }
            Spacer(Modifier.height(10.dp))
            Text(
                option.label,
                color = palette.textPrimary,
                fontSize = 14.sp,
                lineHeight = 18.sp,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(end = 18.dp)
            )
        }
        Box(
            contentAlignment = Alignment.Center,
            modifier = Modifier
                .align(Alignment.TopEnd)
                .size(22.dp)
                .clip(CircleShape)
                .background(if (selected) palette.goldAccent else Color.Transparent)
                .border(1.5.dp, if (selected) palette.goldAccent else palette.textMuted.copy(alpha = 0.4f), CircleShape)
        ) {
            if (selected) Icon(Icons.Default.Check, contentDescription = null, tint = OnGold, modifier = Modifier.size(14.dp))
        }
    }
}

// ---------------------------------------------------------------- feature deck

@Composable
private fun FeaturesStep(
    state: SurveyUiState,
    vm: SurveyViewModel,
    palette: HomeThemePalette,
    onOpenFeature: (SurveyFeature) -> Unit
) {
    val features = state.config?.features.orEmpty()
    StepColumn {
        StepHeader(
            "Already inside",
            "Here's what Eternal Echo can already do",
            "Swipe right if you love it, left if it's not for you, or up if you want to try it.",
            palette
        )
        if (!state.deckDone) {
            FeatureDeck(features, state.deckIndex, palette, vm, onOpenFeature)
        } else {
            DeckSummary(state, vm, palette)
        }
    }
}

@Composable
private fun FeatureDeck(
    features: List<SurveyFeature>,
    deckIndex: Int,
    palette: HomeThemePalette,
    vm: SurveyViewModel,
    onOpenFeature: (SurveyFeature) -> Unit
) {
    val scope = rememberCoroutineScope()
    val haptics = LocalHapticFeedback.current
    val threshold = with(LocalDensity.current) { 110.dp.toPx() }
    val offsetX = remember(deckIndex) { Animatable(0f) }
    val offsetY = remember(deckIndex) { Animatable(0f) }
    var leaving by remember(deckIndex) { mutableStateOf(false) }

    fun fling(reaction: SurveyReaction) {
        if (leaving) return
        leaving = true
        haptics.performHapticFeedback(HapticFeedbackType.TextHandleMove)
        scope.launch {
            val (tx, ty) = when (reaction) {
                SurveyReaction.LOVE -> threshold * 9 to offsetY.value
                SurveyReaction.MEH -> -threshold * 9 to offsetY.value
                SurveyReaction.TRY -> offsetX.value to -threshold * 12
            }
            launch { offsetX.animateTo(tx, tween(320)) }
            offsetY.animateTo(ty, tween(320))
            vm.react(reaction)
        }
    }

    Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.fillMaxWidth()) {
        Box(Modifier.fillMaxWidth().widthIn(max = 420.dp).height(380.dp)) {
            for (depth in 2 downTo 0) {
                val i = deckIndex + depth
                val feature = features.getOrNull(i) ?: continue
                key(feature.key) {
                    val animatedDepth by animateFloatAsState(depth.toFloat(), tween(300), label = "depth")
                    val isTop = depth == 0
                    FeatureCard(
                        feature = feature,
                        position = i + 1,
                        total = features.size,
                        tint = CardTints[i % CardTints.size],
                        palette = palette,
                        stampLove = if (isTop) (offsetX.value / threshold).coerceIn(0f, 1f) else 0f,
                        stampMeh = if (isTop) (-offsetX.value / threshold).coerceIn(0f, 1f) else 0f,
                        stampTry = if (isTop && abs(offsetX.value) < threshold * 0.7f) (-offsetY.value / threshold).coerceIn(0f, 1f) else 0f,
                        onOpen = { onOpenFeature(feature) },
                        modifier = Modifier
                            .zIndex(-depth.toFloat())
                            .fillMaxSize()
                            .graphicsLayer {
                                if (isTop) {
                                    translationX = offsetX.value
                                    translationY = offsetY.value.coerceAtMost(40.dp.toPx())
                                    rotationZ = offsetX.value / 22f
                                } else {
                                    translationY = animatedDepth * 14.dp.toPx()
                                    scaleX = 1f - animatedDepth * 0.05f
                                    scaleY = 1f - animatedDepth * 0.05f
                                    alpha = 1f - animatedDepth * 0.3f
                                }
                            }
                            .then(
                                if (isTop) Modifier.pointerInput(deckIndex) {
                                    detectDragGestures(
                                        onDragEnd = {
                                            val x = offsetX.value
                                            val y = offsetY.value
                                            when {
                                                x > threshold -> fling(SurveyReaction.LOVE)
                                                x < -threshold -> fling(SurveyReaction.MEH)
                                                y < -threshold && abs(x) < threshold * 0.8f -> fling(SurveyReaction.TRY)
                                                else -> scope.launch {
                                                    launch { offsetX.animateTo(0f, spring(dampingRatio = 0.6f)) }
                                                    offsetY.animateTo(0f, spring(dampingRatio = 0.6f))
                                                }
                                            }
                                        },
                                        onDragCancel = {
                                            scope.launch {
                                                launch { offsetX.animateTo(0f) }
                                                offsetY.animateTo(0f)
                                            }
                                        }
                                    ) { change, drag ->
                                        if (leaving) return@detectDragGestures
                                        change.consume()
                                        scope.launch {
                                            offsetX.snapTo(offsetX.value + drag.x)
                                            offsetY.snapTo(offsetY.value + drag.y)
                                        }
                                    }
                                } else Modifier
                            )
                    )
                }
            }
        }

        Spacer(Modifier.height(20.dp))
        Row(
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(14.dp)
        ) {
            IconButton(onClick = vm::undoCard, enabled = deckIndex > 0) {
                Icon(
                    Icons.AutoMirrored.Filled.Undo, contentDescription = "Undo last card",
                    tint = palette.textMuted.copy(alpha = if (deckIndex > 0) 1f else 0.3f)
                )
            }
            ReactionButton("🙅", "Not for me", Color(0xFFF43F5E), 64.dp) { fling(SurveyReaction.MEH) }
            ReactionButton("👀", "Want to try", Color(0xFF0EA5E9), 56.dp) { fling(SurveyReaction.TRY) }
            ReactionButton("😍", "Love it", Color(0xFF10B981), 64.dp) { fling(SurveyReaction.LOVE) }
            TextButton(onClick = vm::skipDeck) { Text("Skip", color = palette.textMuted, fontWeight = FontWeight.Bold, fontSize = 12.sp) }
        }
    }
}

@Composable
private fun FeatureCard(
    feature: SurveyFeature,
    position: Int,
    total: Int,
    tint: Color,
    palette: HomeThemePalette,
    stampLove: Float,
    stampMeh: Float,
    stampTry: Float,
    onOpen: () -> Unit,
    modifier: Modifier = Modifier
) {
    Card(
        shape = RoundedCornerShape(28.dp),
        colors = CardDefaults.cardColors(containerColor = palette.cardBg),
        border = BorderStroke(1.dp, palette.goldAccent.copy(alpha = 0.25f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 8.dp),
        modifier = modifier
    ) {
        Box(Modifier.fillMaxSize()) {
            Column(Modifier.fillMaxSize()) {
                Box(
                    contentAlignment = Alignment.Center,
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(160.dp)
                        .background(Brush.linearGradient(listOf(tint.copy(alpha = 0.35f), tint.copy(alpha = 0.08f))))
                ) {
                    Text(feature.emoji, fontSize = 68.sp)
                    Text(
                        feature.tag.uppercase(),
                        color = palette.textPrimary,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Black,
                        letterSpacing = 1.sp,
                        modifier = Modifier
                            .align(Alignment.TopStart)
                            .padding(14.dp)
                            .clip(CircleShape)
                            .background(palette.cardBg.copy(alpha = 0.7f))
                            .padding(horizontal = 10.dp, vertical = 4.dp)
                    )
                    Text(
                        "$position/$total",
                        color = palette.textSecondary,
                        fontSize = 12.sp,
                        fontWeight = FontWeight.Black,
                        modifier = Modifier.align(Alignment.TopEnd).padding(16.dp)
                    )
                }
                Column(Modifier.weight(1f).padding(20.dp)) {
                    Text(feature.title, color = palette.textPrimary, fontSize = 20.sp, fontWeight = FontWeight.Black)
                    Spacer(Modifier.height(6.dp))
                    Text(feature.body, color = palette.textSecondary, fontSize = 14.sp, lineHeight = 20.sp)
                    Spacer(Modifier.weight(1f))
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier.clip(RoundedCornerShape(8.dp)).clickable(onClick = onOpen).padding(vertical = 6.dp)
                    ) {
                        Text("Take a look", color = palette.goldAccent, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                        Spacer(Modifier.width(4.dp))
                        Icon(Icons.AutoMirrored.Filled.OpenInNew, contentDescription = null, tint = palette.goldAccent, modifier = Modifier.size(14.dp))
                    }
                }
            }
            Stamp("LOVE IT 😍", Color(0xFF34D399), stampLove, -12f, Modifier.align(Alignment.TopStart).padding(start = 20.dp, top = 28.dp))
            Stamp("NOT FOR ME", Color(0xFFFB7185), stampMeh, 12f, Modifier.align(Alignment.TopEnd).padding(end = 20.dp, top = 28.dp))
            Stamp("WANT TO TRY 👀", Color(0xFF38BDF8), stampTry, 0f, Modifier.align(Alignment.BottomCenter).padding(bottom = 80.dp))
        }
    }
}

@Composable
private fun Stamp(text: String, color: Color, alpha: Float, angle: Float, modifier: Modifier) {
    if (alpha <= 0f) return
    Text(
        text,
        color = color,
        fontWeight = FontWeight.Black,
        fontSize = 18.sp,
        modifier = modifier
            .graphicsLayer { this.alpha = alpha; rotationZ = angle }
            .clip(RoundedCornerShape(12.dp))
            .background(Color.Black.copy(alpha = 0.45f))
            .border(3.dp, color, RoundedCornerShape(12.dp))
            .padding(horizontal = 12.dp, vertical = 6.dp)
    )
}

@Composable
private fun ReactionButton(emoji: String, label: String, color: Color, size: Dp, onClick: () -> Unit) {
    Box(
        contentAlignment = Alignment.Center,
        modifier = Modifier
            .size(size)
            .clip(CircleShape)
            .background(color.copy(alpha = 0.12f))
            .border(2.dp, color.copy(alpha = 0.6f), CircleShape)
            .clickable(role = Role.Button, onClickLabel = label, onClick = onClick)
            .semantics { contentDescription = label }
    ) {
        Text(emoji, fontSize = if (size > 60.dp) 26.sp else 22.sp)
    }
}

@Composable
private fun DeckSummary(state: SurveyUiState, vm: SurveyViewModel, palette: HomeThemePalette) {
    val pop = remember { Animatable(0.3f) }
    LaunchedEffect(Unit) { pop.animateTo(1f, spring(dampingRatio = Spring.DampingRatioMediumBouncy)) }
    val counts = state.answers.reactions.values.groupingBy { it }.eachCount()

    Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.fillMaxWidth().padding(vertical = 24.dp)) {
        Text("🎉", fontSize = 64.sp, modifier = Modifier.scale(pop.value))
        Spacer(Modifier.height(12.dp))
        Text(vm.deckSummary(), color = palette.textPrimary, fontSize = 22.sp, fontWeight = FontWeight.Black, textAlign = TextAlign.Center)
        Spacer(Modifier.height(14.dp))
        FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp, Alignment.CenterHorizontally), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            listOf(
                SurveyReaction.LOVE to "loved",
                SurveyReaction.TRY to "to try",
                SurveyReaction.MEH to "not for me"
            ).forEach { (reaction, word) ->
                Text(
                    "${reaction.emoji} ${counts[reaction] ?: 0} $word",
                    color = palette.textPrimary,
                    fontSize = 13.sp,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier
                        .clip(CircleShape)
                        .background(palette.cardBg)
                        .border(1.dp, palette.cardBorder, CircleShape)
                        .padding(horizontal = 12.dp, vertical = 6.dp)
                )
            }
        }
        TextButton(onClick = vm::replayDeck) {
            Text("Go through the cards again", color = palette.textMuted, fontSize = 12.sp, fontWeight = FontWeight.Bold)
        }
    }
}

// ---------------------------------------------------------------- score, wish, contact

@Composable
private fun NpsStep(state: SurveyUiState, vm: SurveyViewModel, palette: HomeThemePalette) {
    val haptics = LocalHapticFeedback.current
    val score = state.answers.nps
    StepColumn {
        StepHeader("Honest answer", "How likely are you to recommend Eternal Echo to a friend or family member?", null, palette)
        Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.fillMaxWidth()) {
            AnimatedContent(
                targetState = SurveyFlow.npsFace(score),
                transitionSpec = { (scaleIn(initialScale = 0.6f) + fadeIn()).togetherWith(fadeOut()) },
                label = "nps_face"
            ) { face -> Text(face, fontSize = 72.sp) }
            Text(SurveyFlow.npsLabel(score), color = palette.textSecondary, fontSize = 14.sp, fontWeight = FontWeight.Bold)
        }
        Spacer(Modifier.height(24.dp))
        listOf(0..5, 6..10).forEach { range ->
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth()) {
                range.forEach { n ->
                    val selected = score == n
                    val grow by animateFloatAsState(if (selected) 1.1f else 1f, spring(dampingRatio = 0.5f), label = "nps_$n")
                    Box(
                        contentAlignment = Alignment.Center,
                        modifier = Modifier
                            .weight(1f)
                            .aspectRatio(1f)
                            .scale(grow)
                            .clip(RoundedCornerShape(14.dp))
                            .background(if (selected) palette.goldAccent else palette.cardBg)
                            .border(1.dp, if (selected) palette.goldAccent else palette.cardBorder, RoundedCornerShape(14.dp))
                            .selectable(selected = selected, role = Role.RadioButton) {
                                haptics.performHapticFeedback(HapticFeedbackType.TextHandleMove)
                                vm.setNps(n)
                            }
                    ) {
                        Text("$n", color = if (selected) OnGold else palette.textPrimary, fontWeight = FontWeight.Black, fontSize = 16.sp)
                    }
                }
                if (range.count() < 6) Spacer(Modifier.weight(1f))
            }
            Spacer(Modifier.height(8.dp))
        }
        Row(Modifier.fillMaxWidth()) {
            Text("Not likely", color = palette.textMuted, fontSize = 11.sp, modifier = Modifier.weight(1f))
            Text("Very likely", color = palette.textMuted, fontSize = 11.sp)
        }
    }
}

@Composable
private fun surveyFieldColors(palette: HomeThemePalette) = OutlinedTextFieldDefaults.colors(
    focusedBorderColor = palette.goldAccent,
    unfocusedBorderColor = palette.cardBorder,
    focusedTextColor = palette.textPrimary,
    unfocusedTextColor = palette.textPrimary,
    cursorColor = palette.goldAccent,
    focusedContainerColor = palette.cardBg,
    unfocusedContainerColor = palette.cardBg,
    focusedLabelColor = palette.goldAccent,
    unfocusedLabelColor = palette.textMuted,
    focusedPlaceholderColor = palette.textMuted,
    unfocusedPlaceholderColor = palette.textMuted
)

@Composable
private fun WishStep(state: SurveyUiState, vm: SurveyViewModel, palette: HomeThemePalette) {
    StepColumn {
        StepHeader("Dream big", "If you could add one thing to the app, what would it be?", "Optional, but this is where the best ideas come from.", palette)
        OutlinedTextField(
            value = state.answers.wish,
            onValueChange = vm::setWish,
            placeholder = { Text("I wish Eternal Echo could…") },
            minLines = 4,
            maxLines = 8,
            shape = RoundedCornerShape(18.dp),
            colors = surveyFieldColors(palette),
            keyboardOptions = KeyboardOptions(capitalization = KeyboardCapitalization.Sentences),
            supportingText = {
                Text(
                    "${state.answers.wish.length}/1000",
                    color = if (state.answers.wish.length > 900) Color(0xFFF59E0B) else palette.textMuted,
                    modifier = Modifier.fillMaxWidth(),
                    textAlign = TextAlign.End
                )
            },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(12.dp))
        Text("Need inspiration? Tap to add:", color = palette.textSecondary, fontSize = 12.sp, fontWeight = FontWeight.Bold)
        Spacer(Modifier.height(8.dp))
        FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            state.config?.wishIdeas.orEmpty().forEach { idea ->
                Text(
                    "+ $idea",
                    color = palette.textPrimary,
                    fontSize = 12.sp,
                    fontWeight = FontWeight.SemiBold,
                    modifier = Modifier
                        .clip(CircleShape)
                        .background(palette.cardBg)
                        .border(1.dp, palette.cardBorder, CircleShape)
                        .clickable(role = Role.Button) { vm.addIdea(idea) }
                        .padding(horizontal = 12.dp, vertical = 7.dp)
                )
            }
        }
    }
}

@Composable
private fun ContactStep(state: SurveyUiState, vm: SurveyViewModel, palette: HomeThemePalette) {
    val emailInvalid = !SurveyFlow.isEmailValid(state.answers.email)
    val whatsappInvalid = !SurveyFlow.isWhatsappValid(state.answers.whatsapp)
    StepColumn {
        StepHeader("Last step", "Want to hear when your ideas ship?", "Name and email are required. Add WhatsApp if you'd like updates there too.", palette)
        if (state.signedIn) {
            Text(
                "👤  You're signed in, so your answers are saved to your account and you can update them any time.",
                color = palette.textPrimary,
                fontSize = 13.sp,
                modifier = Modifier
                    .fillMaxWidth()
                    .clip(RoundedCornerShape(16.dp))
                    .background(palette.primaryAccent.copy(alpha = 0.14f))
                    .padding(14.dp)
            )
            Spacer(Modifier.height(16.dp))
        }
        OutlinedTextField(
            value = state.answers.name,
            onValueChange = vm::setName,
            label = { Text("Your name *") },
            singleLine = true,
            shape = RoundedCornerShape(16.dp),
            colors = surveyFieldColors(palette),
            keyboardOptions = KeyboardOptions(capitalization = KeyboardCapitalization.Words, imeAction = ImeAction.Next),
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(12.dp))
        OutlinedTextField(
            value = state.answers.email,
            onValueChange = vm::setEmail,
            label = { Text("Email *") },
            singleLine = true,
            isError = emailInvalid,
            supportingText = { if (emailInvalid) Text("That email doesn't look right.") },
            shape = RoundedCornerShape(16.dp),
            colors = surveyFieldColors(palette),
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email, imeAction = ImeAction.Next),
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(12.dp))
        OutlinedTextField(
            value = state.answers.whatsapp,
            onValueChange = vm::setWhatsapp,
            label = { Text("WhatsApp number (optional)") },
            placeholder = { Text("+92 300 1234567") },
            singleLine = true,
            isError = whatsappInvalid,
            supportingText = {
                Text(if (whatsappInvalid) "Include your country code, e.g. +92 300 1234567." else "💬 Get updates on WhatsApp.")
            },
            shape = RoundedCornerShape(16.dp),
            colors = surveyFieldColors(palette),
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone, imeAction = ImeAction.Done),
            keyboardActions = KeyboardActions(onDone = { vm.submit() }),
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(12.dp))
        Row(
            verticalAlignment = Alignment.CenterVertically,
            modifier = Modifier
                .fillMaxWidth()
                .clip(RoundedCornerShape(16.dp))
                .background(palette.cardBg)
                .border(1.dp, palette.cardBorder, RoundedCornerShape(16.dp))
                .toggleable(value = state.answers.notify, role = Role.Switch, onValueChange = { vm.toggleNotify() })
                .padding(horizontal = 16.dp, vertical = 12.dp)
        ) {
            Column(Modifier.weight(1f)) {
                Text("🔔 Tell me when my wish is built", color = palette.textPrimary, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                Text("One email when it happens, nothing else.", color = palette.textMuted, fontSize = 12.sp)
            }
            Switch(
                checked = state.answers.notify,
                onCheckedChange = null,
                colors = SwitchDefaults.colors(checkedTrackColor = palette.goldAccent, checkedThumbColor = Color.White)
            )
        }
        state.submitError?.let {
            Spacer(Modifier.height(12.dp))
            Text(
                it,
                color = Color(0xFFFDA4AF),
                fontWeight = FontWeight.SemiBold,
                fontSize = 13.sp,
                modifier = Modifier
                    .fillMaxWidth()
                    .clip(RoundedCornerShape(16.dp))
                    .background(Color(0xFFF43F5E).copy(alpha = 0.12f))
                    .padding(14.dp)
            )
        }
    }
}

// ---------------------------------------------------------------- done

@Composable
private fun DoneStep(
    state: SurveyUiState,
    vm: SurveyViewModel,
    palette: HomeThemePalette,
    onOpenFeature: (SurveyFeature) -> Unit,
    onBack: () -> Unit
) {
    val context = LocalContext.current
    val config = state.config ?: return
    val picks = remember(state.answers) { SurveyFlow.picks(config, state.answers) }
    val firstName = state.answers.name.trim().substringBefore(' ')
    val medal = remember { Animatable(0.4f) }
    LaunchedEffect(Unit) { medal.animateTo(1f, spring(dampingRatio = 0.45f, stiffness = Spring.StiffnessLow)) }

    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(horizontal = 20.dp, vertical = 16.dp)
    ) {
        Box(
            contentAlignment = Alignment.Center,
            modifier = Modifier
                .size(130.dp)
                .graphicsLayer { scaleX = medal.value; scaleY = medal.value; rotationZ = (1f - medal.value) * -30f }
                .clip(CircleShape)
                .background(Brush.linearGradient(listOf(Color(0xFFF6E58D), Color(0xFFD4AF37), Color(0xFF997A1D))))
                .padding(4.dp)
                .clip(CircleShape)
                .background(palette.cardBg)
        ) {
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                Text("🏅", fontSize = 40.sp)
                Text("FOUNDING", color = palette.goldAccent, fontSize = 9.sp, fontWeight = FontWeight.Black, letterSpacing = 2.sp)
                Text("VOICE", color = palette.goldAccent, fontSize = 9.sp, fontWeight = FontWeight.Black, letterSpacing = 2.sp)
            }
        }
        Spacer(Modifier.height(20.dp))
        Text(
            if (firstName.isNotBlank()) "JazakAllahu Khairan, $firstName!" else "JazakAllahu Khairan!",
            color = palette.textPrimary, fontSize = 28.sp, lineHeight = 33.sp, fontWeight = FontWeight.Black, textAlign = TextAlign.Center,
            modifier = Modifier.semantics { heading() }
        )
        Spacer(Modifier.height(8.dp))
        Text(
            buildAnnotatedString {
                append("Your answers are now part of our roadmap. You earned the ")
                withStyle(SpanStyle(color = palette.goldAccent, fontWeight = FontWeight.Bold)) { append("Founding Voice") }
                append(" badge and ")
                withStyle(SpanStyle(color = palette.goldAccent, fontWeight = FontWeight.Bold)) { append("${state.xp} XP") }
                append(".")
            },
            color = palette.textSecondary, fontSize = 15.sp, lineHeight = 21.sp, textAlign = TextAlign.Center
        )

        if (picks.isNotEmpty()) {
            Spacer(Modifier.height(24.dp))
            Text("PICKED FOR YOU", color = palette.goldAccent, fontSize = 12.sp, fontWeight = FontWeight.Black, letterSpacing = 2.sp)
            Spacer(Modifier.height(10.dp))
            picks.forEachIndexed { i, feature ->
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 10.dp)
                        .clip(RoundedCornerShape(18.dp))
                        .background(palette.cardBg)
                        .border(1.dp, palette.cardBorder, RoundedCornerShape(18.dp))
                        .clickable(role = Role.Button) { onOpenFeature(feature) }
                        .padding(14.dp)
                ) {
                    Box(
                        contentAlignment = Alignment.Center,
                        modifier = Modifier
                            .size(48.dp)
                            .clip(RoundedCornerShape(14.dp))
                            .background(CardTints[(config.features.indexOf(feature).takeIf { it >= 0 } ?: i) % CardTints.size].copy(alpha = 0.22f))
                    ) { Text(feature.emoji, fontSize = 24.sp) }
                    Column(Modifier.weight(1f).padding(horizontal = 12.dp)) {
                        Text(feature.title, color = palette.textPrimary, fontWeight = FontWeight.Bold, fontSize = 15.sp)
                        Text(feature.body, color = palette.textMuted, fontSize = 12.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
                    }
                }
            }
        }

        Spacer(Modifier.height(16.dp))
        GoldButton(
            "📣 Invite a friend to vote",
            onClick = {
                val send = Intent(Intent.ACTION_SEND).apply {
                    type = "text/plain"
                    putExtra(
                        Intent.EXTRA_TEXT,
                        "Help shape Eternal Echo, a Quran & Seerah learning app. It's a 2-minute survey: ${DeepLinkHelper.BASE_WEB_URL}/survey"
                    )
                }
                runCatching { context.startActivity(Intent.createChooser(send, "Invite a friend")) }
            },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(10.dp))
        OutlinedButton(
            onClick = onBack,
            shape = RoundedCornerShape(16.dp),
            border = BorderStroke(1.dp, palette.cardBorder),
            modifier = Modifier.fillMaxWidth().heightIn(min = 52.dp)
        ) { Text("Back to Eternal Echo", color = palette.textPrimary, fontWeight = FontWeight.Bold) }
    }
}

// ---------------------------------------------------------------- confetti

private class ConfettiPiece(
    val x: Float, val y: Float, val vx: Float, val vy: Float,
    val size: Float, val spin: Float, val color: Color, val star: Boolean
)

@Composable
private fun Confetti(modifier: Modifier = Modifier) {
    val colors = listOf(Color(0xFFD4AF37), Color(0xFFF6E58D), Color(0xFF10B981), Color(0xFF34D399), Color.White, Color(0xFFE6CA65))
    val pieces = remember {
        List(140) {
            ConfettiPiece(
                x = Random.nextFloat() - 0.5f,
                y = 0f,
                vx = (Random.nextFloat() - 0.5f) * 14f,
                vy = -Random.nextFloat() * 13f - 4f,
                size = Random.nextFloat() * 7f + 5f,
                spin = (Random.nextFloat() - 0.5f) * 0.3f,
                color = colors[Random.nextInt(colors.size)],
                star = Random.nextFloat() > 0.55f
            )
        }
    }
    var seconds by remember { mutableFloatStateOf(0f) }
    LaunchedEffect(Unit) {
        val start = withFrameNanos { it }
        while (seconds < 3.4f) {
            seconds = (withFrameNanos { it } - start) / 1_000_000_000f
        }
    }
    if (seconds >= 3.4f) return

    val density = LocalDensity.current.density
    Canvas(modifier) {
        val frames = seconds * 60f
        val alpha = (1f - seconds / 3.2f).coerceIn(0f, 1f)
        val star = Path()
        pieces.forEach { p ->
            // Closed-form version of "add gravity, then move" per 60 fps frame, with slight drag on x.
            val px = size.width / 2 + p.x * 120f * density + p.vx * density * frames * 0.9f
            val py = size.height * 0.3f + p.vy * density * frames + 0.5f * 0.32f * density * frames * frames
            val s = p.size * density
            translate(px, py) {
                rotate(degrees = (p.spin * frames) * 57.3f, pivot = Offset.Zero) {
                    if (p.star) {
                        star.reset()
                        for (k in 0 until 8) {
                            val r = if (k % 2 == 0) s * 0.8f else s * 0.36f
                            val a = (Math.PI / 4 * k).toFloat()
                            if (k == 0) star.moveTo(r * cos(a), r * sin(a)) else star.lineTo(r * cos(a), r * sin(a))
                        }
                        star.close()
                        drawPath(star, p.color, alpha = alpha)
                    } else {
                        drawRect(p.color, topLeft = Offset(-s / 2, -s / 4), size = Size(s, s / 2), alpha = alpha)
                    }
                }
            }
        }
    }
}
