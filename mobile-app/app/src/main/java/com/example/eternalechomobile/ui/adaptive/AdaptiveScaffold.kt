package com.example.eternalechomobile.ui.adaptive

import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.List
import androidx.compose.material.icons.filled.Favorite
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Star
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.example.eternalechomobile.config.AppConfig

data class NavigationDestination(
    val index: Int,
    val title: String,
    val icon: androidx.compose.ui.graphics.vector.ImageVector
)

val defaultNavigationDestinations: List<NavigationDestination>
    get() = buildList {
        add(NavigationDestination(0, "Surahs", Icons.AutoMirrored.Filled.List))
        add(NavigationDestination(1, "Leaderboard", Icons.Default.Star))
        add(NavigationDestination(2, "Seerah", Icons.Default.Person))
        add(NavigationDestination(3, "History", Icons.Default.Info))
        if (AppConfig.IS_DUAS_FEATURE_ENABLED) {
            add(NavigationDestination(4, "Duas", Icons.Default.Favorite))
        }
    }

@Composable
fun AdaptiveNavigationScaffold(
    adaptiveInfo: WindowAdaptiveInfo,
    selectedTab: Int,
    onTabSelected: (Int) -> Unit,
    topBar: @Composable () -> Unit,
    modifier: Modifier = Modifier,
    destinations: List<NavigationDestination> = defaultNavigationDestinations,
    content: @Composable (PaddingValues) -> Unit
) {
    if (adaptiveInfo.shouldUseNavigationRail) {
        // Landscape or Tablet / Expanded screen: Navigation Rail on the start side
        Row(modifier = modifier.fillMaxSize()) {
            NavigationRail(
                containerColor = MaterialTheme.colorScheme.primaryContainer,
                contentColor = MaterialTheme.colorScheme.onPrimaryContainer,
                modifier = Modifier.fillMaxHeight()
            ) {
                Spacer(modifier = Modifier.height(8.dp))
                for (dest in destinations) {
                    NavigationRailItem(
                        selected = selectedTab == dest.index,
                        onClick = { onTabSelected(dest.index) },
                        icon = { Icon(dest.icon, contentDescription = dest.title) },
                        label = { Text(dest.title) }
                    )
                }
            }

            Column(
                modifier = Modifier
                    .weight(1f)
                    .fillMaxHeight()
            ) {
                topBar()
                Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
                    content(PaddingValues(0.dp))
                }
            }
        }
    } else {
        // Compact phone portrait or TableTop (Flex) mode: Bottom Navigation Bar
        Scaffold(
            topBar = topBar,
            bottomBar = {
                NavigationBar(
                    containerColor = MaterialTheme.colorScheme.primaryContainer
                ) {
                    for (dest in destinations) {
                        NavigationBarItem(
                            selected = selectedTab == dest.index,
                            onClick = { onTabSelected(dest.index) },
                            icon = { Icon(dest.icon, contentDescription = dest.title) },
                            label = { Text(dest.title) }
                        )
                    }
                }
            },
            modifier = modifier.fillMaxSize()
        ) { paddingValues ->
            content(paddingValues)
        }
    }
}
