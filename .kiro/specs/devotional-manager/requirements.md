# Requirements Document

## Introduction

A mobile-first devotional management application built on top of an existing Laravel + React + Inertia.js starter kit. The app serves a couple (two users) who want to do daily devotions together using primarily the King James Bible. It has two main modes: Thematic Devotions (structured devotional content organized by topics like faith, forgiveness, poverty, shame) and Bible Study (systematic Bible reading plans, word studies, and Greek origin exploration). Seventh-day Adventist beliefs and interpretations of scripture may be lightly woven into devotional content without heavy doctrinal emphasis.

## Glossary

- **Devotional_App**: The overall devotional management application, encompassing both Thematic Devotions and Bible Study modes.
- **Theme**: A named topic (e.g., faith, forgiveness, poverty, shame) that groups related devotional entries together.
- **Devotional_Entry**: A single devotional message within a Theme, containing a title, scripture references, devotional body text, reflection prompts, and optional Adventist insights.
- **Bible_Version**: A specific translation of the Bible available in the app. The King James Version (KJV) is the primary version; additional versions may be available for comparison.
- **Reading_Plan**: A structured schedule for reading through the Bible over a defined period (e.g., one year), with daily assigned passages.
- **Reading_Progress_Tracker**: The component that records which passages a user has completed within a Reading_Plan.
- **Word_Study**: An in-depth exploration of a specific word from scripture, including its Greek (or Hebrew) origin, transliteration, definition, and usage across Bible passages.
- **Scripture_Reference**: A structured pointer to a specific Bible passage, consisting of book name, chapter, and verse range.
- **Bookmark**: A saved reference to a specific Devotional_Entry, Bible passage, or Word_Study that a user wants to revisit.
- **User**: An authenticated person using the Devotional_App. The app is designed for two users (a couple).
- **Service_Worker**: A background script registered by the Devotional_App that intercepts network requests and enables offline functionality by serving cached resources.
- **Web_App_Manifest**: A JSON configuration file that describes the Devotional_App's metadata (name, icons, theme color, display mode) to enable installation on a device's home screen.
- **Offline_Cache**: The set of previously viewed Devotional_Entries, Scripture_References, and application shell assets stored locally by the Service_Worker for offline access.
- **AI_Image_Provider**: An external AI image generation service (e.g., OpenAI DALL-E) that the Devotional_App calls to produce images from text-based prompts.
- **Generated_Image**: An image produced by the AI_Image_Provider based on the content of a Devotional_Entry, stored and associated with that entry for display.
- **Partner**: The other User in a coupled devotional relationship. Two Users are linked together as devotional Partners to enable collaborative study.
- **Observation**: A personal note or reflection added by a User to a Devotional_Entry, visible to the User's Partner for collaborative engagement.
- **Notification**: An alert sent to a User about their Partner's devotional activity (e.g., completing an entry, adding an Observation) or a devotional reminder.
- **Notification_Center**: The in-app view where a User can browse and manage received Notifications.

## Requirements

### Requirement 1: Theme Management

**User Story:** As a User, I want to browse and select devotional themes, so that I can engage with devotional content structured around a specific topic.

#### Acceptance Criteria

1. THE Devotional_App SHALL display a list of all available Themes with their names and descriptions on the themes index page.
2. WHEN a User selects a Theme, THE Devotional_App SHALL display all Devotional_Entries belonging to that Theme in sequential order.
3. THE Devotional_App SHALL display the total number of Devotional_Entries and the number of completed entries for each Theme on the themes index page.
4. WHEN no Themes exist, THE Devotional_App SHALL display an empty state message with a prompt to create a new Theme.

### Requirement 2: Devotional Entry Management

**User Story:** As a User, I want to create and manage devotional entries within a theme, so that I can build structured devotional content around specific topics.

#### Acceptance Criteria

1. WHEN a User creates a Devotional_Entry, THE Devotional_App SHALL require a title, at least one Scripture_Reference, and a devotional body text.
2. WHEN a User creates a Devotional_Entry, THE Devotional_App SHALL associate the entry with exactly one Theme.
3. THE Devotional_App SHALL allow a Devotional_Entry to include optional reflection prompts and optional Adventist insight text.
4. WHEN a User edits a Devotional_Entry, THE Devotional_App SHALL validate that the title, Scripture_Reference, and body text fields remain populated.
5. WHEN a User deletes a Devotional_Entry, THE Devotional_App SHALL prompt for confirmation before removing the entry.
6. THE Devotional_App SHALL store the display order of Devotional_Entries within a Theme and allow reordering.

### Requirement 3: Scripture Reference and Bible Display

**User Story:** As a User, I want to view Bible passages inline within devotionals and select different Bible versions, so that I can read and compare scripture easily.

#### Acceptance Criteria

1. WHEN a Devotional_Entry contains a Scripture_Reference, THE Devotional_App SHALL display the full passage text inline using the currently selected Bible_Version.
2. THE Devotional_App SHALL default to the King James Version (KJV) as the active Bible_Version.
3. WHEN a User switches the active Bible_Version, THE Devotional_App SHALL re-render all displayed Scripture_References using the newly selected Bible_Version.
4. IF a Scripture_Reference cannot be resolved for the selected Bible_Version, THEN THE Devotional_App SHALL display an error message identifying the unresolvable reference.
5. THE Devotional_App SHALL parse Scripture_References in standard format (e.g., "John 3:16", "Psalm 23:1-6", "Romans 8:28-39").

### Requirement 4: Bible Reading Plan

**User Story:** As a User, I want to follow a structured Bible reading plan, so that I can read through the Bible systematically over a year.

#### Acceptance Criteria

1. THE Devotional_App SHALL provide at least one default Reading_Plan that covers the entire Bible over 365 days.
2. WHEN a User activates a Reading_Plan, THE Reading_Progress_Tracker SHALL record the start date and calculate daily assigned passages.
3. WHEN a User opens the Bible Study mode, THE Devotional_App SHALL display the current day's assigned passages from the active Reading_Plan.
4. WHEN a User marks a daily passage as complete, THE Reading_Progress_Tracker SHALL record the completion date and update the overall progress percentage.
5. WHEN a User has missed one or more days in the Reading_Plan, THE Devotional_App SHALL display the missed passages and allow the User to mark them as complete or skip them.
6. THE Devotional_App SHALL display the overall Reading_Plan progress as a percentage and a visual progress indicator.

### Requirement 5: Word Study and Greek Origins

**User Story:** As a User, I want to explore the Greek and Hebrew origins of words in scripture, so that I can gain deeper understanding of Biblical text.

#### Acceptance Criteria

1. WHEN a User selects a word within a displayed scripture passage, THE Devotional_App SHALL display available Word_Study information for that word.
2. WHEN a Word_Study is available, THE Devotional_App SHALL display the original Greek or Hebrew word, its transliteration, its definition, and its Strong's Concordance number.
3. WHEN a User views a Word_Study, THE Devotional_App SHALL list other Bible passages where the same original-language word appears.
4. WHEN no Word_Study data is available for a selected word, THE Devotional_App SHALL display a message indicating that no study data is available for that word.
5. THE Devotional_App SHALL allow a User to save a Word_Study as a Bookmark for later review.

### Requirement 6: Bookmarks and Favorites

**User Story:** As a User, I want to bookmark devotional entries, Bible passages, and word studies, so that I can quickly return to meaningful content.

#### Acceptance Criteria

1. WHEN a User bookmarks a Devotional_Entry, THE Devotional_App SHALL save the reference with a timestamp and display the bookmark in the User's bookmark list.
2. WHEN a User bookmarks a Scripture_Reference, THE Devotional_App SHALL save the book, chapter, and verse range along with the active Bible_Version.
3. WHEN a User removes a Bookmark, THE Devotional_App SHALL remove the bookmark from the list after confirmation.
4. THE Devotional_App SHALL display all Bookmarks grouped by type (Devotional_Entry, Scripture_Reference, Word_Study) on the bookmarks page.

### Requirement 7: Mobile-First Responsive Interface

**User Story:** As a User, I want to use the devotional app comfortably on my mobile device, so that I can do devotions anywhere.

#### Acceptance Criteria

1. THE Devotional_App SHALL render all pages using a mobile-first responsive layout that adapts to screen widths from 320px to 1440px.
2. THE Devotional_App SHALL use touch-friendly tap targets with a minimum size of 44x44 CSS pixels for all interactive elements.
3. THE Devotional_App SHALL provide bottom navigation for switching between Thematic Devotions, Bible Study, Bookmarks, and Settings on mobile viewports (below 768px).
4. WHILE a User is on a viewport wider than 768px, THE Devotional_App SHALL display a sidebar navigation instead of bottom navigation.
5. THE Devotional_App SHALL use readable font sizes (minimum 16px body text) and adequate line spacing (minimum 1.5 line-height) for scripture reading on mobile devices.

### Requirement 8: Devotional Completion Tracking

**User Story:** As a User, I want to track which devotional entries I have completed, so that I can see my progress through a theme.

#### Acceptance Criteria

1. WHEN a User marks a Devotional_Entry as complete, THE Devotional_App SHALL record the completion with the User's identity and a timestamp.
2. THE Devotional_App SHALL display a completion indicator on each Devotional_Entry in the theme view.
3. WHEN both Users have marked a Devotional_Entry as complete, THE Devotional_App SHALL display a distinct "completed together" indicator on that entry.
4. THE Devotional_App SHALL calculate and display the percentage of completed Devotional_Entries per Theme for each User.

### Requirement 9: Theme Creation and Editing

**User Story:** As a User, I want to create and edit devotional themes, so that I can organize devotional content around topics that matter to me.

#### Acceptance Criteria

1. WHEN a User creates a Theme, THE Devotional_App SHALL require a unique name and an optional description.
2. WHEN a User creates a Theme with a name that already exists, THE Devotional_App SHALL display a validation error indicating the name is taken.
3. WHEN a User edits a Theme, THE Devotional_App SHALL allow updating the name and description while preserving all associated Devotional_Entries.
4. WHEN a User deletes a Theme, THE Devotional_App SHALL prompt for confirmation and inform the User that all associated Devotional_Entries will be removed.

### Requirement 10: Daily Devotional View

**User Story:** As a User, I want a focused daily devotional view, so that I can read and reflect on one devotional entry at a time without distraction.

#### Acceptance Criteria

1. WHEN a User opens a Devotional_Entry, THE Devotional_App SHALL display the title, scripture passage text, devotional body, reflection prompts, and Adventist insights in a single scrollable view.
2. THE Devotional_App SHALL provide navigation controls to move to the previous and next Devotional_Entry within the same Theme.
3. WHEN a User reaches the last Devotional_Entry in a Theme, THE Devotional_App SHALL display a completion summary for that Theme.
4. THE Devotional_App SHALL display the scripture passage text using the currently selected Bible_Version within the daily devotional view.

### Requirement 11: Progressive Web App (PWA) Support

**User Story:** As a User, I want to install the devotional app on my device and access previously viewed content offline, so that I can do devotions without a reliable internet connection.

#### Acceptance Criteria

1. THE Devotional_App SHALL include a Web_App_Manifest that specifies the application name, icons (at minimum 192x192 and 512x512 pixels), theme color, background color, and a display mode of "standalone".
2. THE Devotional_App SHALL register a Service_Worker that caches the application shell (HTML, CSS, JavaScript, and font assets) on first load.
3. WHEN a User views a Devotional_Entry, THE Service_Worker SHALL store that Devotional_Entry and its associated Scripture_Reference text in the Offline_Cache.
4. WHEN a User views a scripture passage, THE Service_Worker SHALL store the rendered passage text for the active Bible_Version in the Offline_Cache.
5. WHILE the device has no network connection, THE Devotional_App SHALL serve previously cached Devotional_Entries and Scripture_References from the Offline_Cache.
6. WHILE the device has no network connection, THE Devotional_App SHALL display a visual indicator informing the User that the app is in offline mode.
7. IF a User requests content that is not available in the Offline_Cache while offline, THEN THE Devotional_App SHALL display a message indicating the content is unavailable offline and suggest reconnecting to the internet.
8. WHEN network connectivity is restored, THE Service_Worker SHALL synchronize any pending offline actions (such as marking Devotional_Entries as complete) with the server.
9. THE Devotional_App SHALL prompt eligible Users to install the app to their home screen using the browser's native install prompt when the PWA installability criteria are met.
10. THE Devotional_App SHALL function as a standalone application (without browser chrome) when launched from the device's home screen.

### Requirement 12: AI-Generated Devotional Images

**User Story:** As a User, I want to generate an AI image that reflects the content of a devotional entry, so that I can enrich my devotional experience with a meaningful visual.

#### Acceptance Criteria

1. WHEN a User views a Devotional_Entry, THE Devotional_App SHALL display a button to request a Generated_Image for that entry.
2. WHEN a User requests a Generated_Image, THE Devotional_App SHALL construct a text prompt derived from the Devotional_Entry title, Scripture_Reference text, and devotional body content and send the prompt to the AI_Image_Provider.
3. WHILE the AI_Image_Provider is processing the image request, THE Devotional_App SHALL display a loading indicator to the User.
4. WHEN the AI_Image_Provider returns a Generated_Image, THE Devotional_App SHALL store the image and associate it with the originating Devotional_Entry.
5. WHEN a Devotional_Entry has an associated Generated_Image, THE Devotional_App SHALL display the Generated_Image within the daily devotional view.
6. WHEN a User requests a new Generated_Image for a Devotional_Entry that already has one, THE Devotional_App SHALL replace the existing Generated_Image with the newly generated one after User confirmation.
7. IF the AI_Image_Provider returns an error or is unreachable, THEN THE Devotional_App SHALL display a message indicating that image generation is unavailable and allow the User to retry.
8. THE Devotional_App SHALL store AI_Image_Provider API credentials in server-side configuration and keep the credentials inaccessible to the frontend.

### Requirement 13: Collaborative Notes and Observations

**User Story:** As a User, I want to add personal notes and observations on devotional entries that my Partner can see, so that we can share reflections and study together even when apart.

#### Acceptance Criteria

1. WHEN a User views a Devotional_Entry, THE Devotional_App SHALL display a form to add an Observation to that entry.
2. WHEN a User submits an Observation, THE Devotional_App SHALL associate the Observation with the originating Devotional_Entry and the authoring User.
3. THE Devotional_App SHALL display all Observations for a Devotional_Entry alongside the devotional content, identifying each Observation by its author.
4. WHEN a User has a linked Partner, THE Devotional_App SHALL display the Partner's Observations on shared Devotional_Entries.
5. WHEN a User edits an Observation, THE Devotional_App SHALL update the Observation text and record the edit timestamp.
6. WHEN a User deletes an Observation, THE Devotional_App SHALL remove the Observation after confirmation.
7. THE Devotional_App SHALL display Observations in chronological order within each Devotional_Entry.
8. WHEN a User who has no linked Partner views a Devotional_Entry, THE Devotional_App SHALL allow the User to add Observations without displaying Partner-related collaboration features.

### Requirement 14: Partner Notifications

**User Story:** As a User, I want to receive notifications about my Partner's devotional activity, so that we can stay connected and motivated in our devotional journey together.

#### Acceptance Criteria

1. WHEN a Partner completes a Devotional_Entry, THE Devotional_App SHALL send a Notification to the other User indicating which entry was completed.
2. WHEN a Partner adds an Observation to a Devotional_Entry, THE Devotional_App SHALL send a Notification to the other User indicating a new Observation is available.
3. WHEN a Partner starts a new Theme, THE Devotional_App SHALL send a Notification to the other User indicating the new Theme.
4. THE Devotional_App SHALL deliver Notifications as push notifications through the Service_Worker when the User has granted push notification permission.
5. THE Devotional_App SHALL provide a Notification_Center page where a User can view all received Notifications in reverse chronological order.
6. WHEN a User opens the Notification_Center, THE Devotional_App SHALL mark all unread Notifications as read.
7. THE Devotional_App SHALL display an unread Notification count badge on the Notification_Center navigation item.
8. THE Devotional_App SHALL allow a User to configure notification preferences, enabling or disabling each notification type (completion, Observation, new Theme, reminders) independently.
9. WHEN a User disables a notification type, THE Devotional_App SHALL stop sending that type of Notification to the User.
10. IF push notification permission is not granted, THEN THE Devotional_App SHALL deliver Notifications only within the in-app Notification_Center.
