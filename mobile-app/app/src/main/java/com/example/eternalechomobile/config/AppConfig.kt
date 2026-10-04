package com.asloobulhayat.eternalecho.config

/**
 * Global configuration and feature flags for The Eternal Echo mobile companion app.
 */
object AppConfig {
    /**
     * Feature flag for the Duas (Sacred Supplications) library.
     *
     * Set to `false` until the Duas backend API has been deployed to production.
     * When `false`:
     *  - The Duas tab and entry points are disabled/hidden to prevent network errors or empty screens.
     *
     * When `true`:
     *  - The Duas tab is activated in the main navigation bar and fully accessible to users.
     */
    const val IS_DUAS_FEATURE_ENABLED = false
    const val IS_LENS_FEATURE_ENABLED = false
}
