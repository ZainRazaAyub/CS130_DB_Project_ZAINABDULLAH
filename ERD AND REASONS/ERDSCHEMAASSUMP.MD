# Database Schema: Video Sharing Platform

## 1. Users Table  
**Purpose**: Stores user credentials and basic profile information.  
**Primary Key**: `user_id`  
**Constraints**:  
- `email` is marked as UNIQUE to prevent duplicate accounts.  
- `password` is stored as `VARCHAR(255)` to accommodate hashed passwords.  

---

## 2. Videos Table  
**Purpose**: Stores metadata and upload details of each video.  
**Foreign Key**: `user_id` → `Users(user_id)`  
**Design Choice**: Optional fields like `description` and `file_url` provide flexibility.  

---

## 3. Comments Table  
**Purpose**: Enables users to post comments on videos.  
**Foreign Keys**:  
- `video_id` → `Videos(video_id)`  
- `user_id` → `Users(user_id)`  
**Assumption**: Every comment must be associated with a valid video and user.  

---

## 4. Likes Table  
**Purpose**: Tracks positive reactions to videos.  
**Foreign Keys**:  
- `user_id` → `Users(user_id)`  
- `video_id` → `Videos(video_id)`  
**Assumption**: A user can like a video only once.  

---

## 5. Dislikes Table  
**Purpose**: Tracks negative reactions to videos.  
**Foreign Keys**:  
- `user_id` → `Users(user_id)`  
- `video_id` → `Videos(video_id)`  
**Assumption**: A user can dislike a video only once.  

---

## 6. Playlists Table  
**Purpose**: Stores user-created playlists.  
**Foreign Key**: `user_id` → `Users(user_id)`  

---

## 7. Playlist_Videos Table  
**Purpose**: Junction table linking playlists and videos in a many-to-many relationship.  
**Foreign Keys**:  
- `playlist_id` → `Playlists(playlist_id)`  
- `video_id` → `Videos(video_id)`  
**Assumption**: A playlist may contain multiple videos and a video can appear in multiple playlists.  

---

## 8. Reports Table  
**Purpose**: Allows users to report inappropriate or problematic videos.  
**Foreign Keys**:  
- `user_id` (reporter) → `Users(user_id)`  
- `video_id` (reported) → `Videos(video_id)`  
**Assumption**: Repeated reports are allowed; no constraint to prevent duplicate reports.  

---

## 9. Subscription Table  
**Purpose**: Represents "user A subscribes to user B" relationships.  
**Composite Primary Key**: (`subscriber_id`, `subscribed_to_id`)  
**Foreign Keys**:  
- Both fields reference `Users(user_id)`  
**Assumption**: A user cannot subscribe to the same user more than once.  
**Self-referencing**: Both foreign keys point to the `Users` table.  

---

## 10. Tags Table  
**Purpose**: Stores reusable tag names for categorization.  
**Primary Key**: `tag_id`  

---

## 11. Video_Tags Table  
**Purpose**: Manages the many-to-many relationship between videos and tags.  
**Foreign Keys**:  
- `video_id` → `Videos(video_id)`  
- `tag_id` → `Tags(tag_id)`  
**Assumption**: Tags are reused across multiple videos.  

---

## 12. Video_Ratings Table  
**Purpose**: Allows users to assign a numerical rating to videos.  
**Constraints**:  
- `rating` must be between 1 and 5.  
**Assumption**: A user can rate a video only once (enforced with a unique constraint on `user_id`, `video_id`).  
