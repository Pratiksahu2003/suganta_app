# Firebase Push Notifications (Flutter)

This guide explains end-to-end Flutter implementation for Firebase Cloud Messaging (FCM) with the backend APIs added in this project.

---

## 1) Backend Endpoints

All endpoints require `Authorization: Bearer <token>`.

- `POST /api/v1/notifications/push-token`
  - Registers/updates current device FCM token
- `DELETE /api/v1/notifications/push-token`
  - Removes token on logout/uninstall

### Request Body (register)

```json
{
  "token": "fcm-device-token",
  "platform": "android",
  "device_name": "Samsung S23"
}
```

`platform` allowed values: `android`, `ios`, `web`, `unknown`

### Request Body (remove)

```json
{
  "token": "fcm-device-token"
}
```

---

## 2) Flutter Dependencies

Add in `pubspec.yaml`:

```yaml
dependencies:
  firebase_core: ^3.6.0
  firebase_messaging: ^15.1.3
  flutter_local_notifications: ^17.2.3
  dio: ^5.7.0
```

Then run:

```bash
flutter pub get
```

---

## 3) Firebase Setup in Flutter

Initialize Firebase at app start:

```dart
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}

Future<void> initFirebase() async {
  await Firebase.initializeApp();
  FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
}
```

Call `await initFirebase();` before `runApp(...)`.

---

## 4) Platform Setup (Android + iOS)

### Android

1. Place `google-services.json` in `android/app/`.
2. Ensure Gradle Firebase plugin is configured.
3. Add notification permission for Android 13+ in `android/app/src/main/AndroidManifest.xml`:

```xml
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
```

### iOS

1. Place `GoogleService-Info.plist` in `ios/Runner/`.
2. Enable **Push Notifications** capability in Xcode.
3. Enable **Background Modes** -> `Remote notifications`.
4. Upload APNs key/certificate in Firebase Console.

---

## 5) Register Token to API

Use this after login (or whenever token refreshes):

```dart
import 'dart:io' show Platform;
import 'package:dio/dio.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

class PushApiService {
  PushApiService(this._dio, this._baseUrl, this._accessToken);

  final Dio _dio;
  final String _baseUrl;
  final String _accessToken;

  Future<void> registerPushToken({String? deviceName}) async {
    final messaging = FirebaseMessaging.instance;

    // iOS requires explicit permission request
    await messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
      provisional: false,
    );

    final token = await messaging.getToken();
    if (token == null || token.isEmpty) return;

    final platform = Platform.isAndroid
        ? 'android'
        : Platform.isIOS
            ? 'ios'
            : 'unknown';

    await _dio.post(
      '$_baseUrl/api/v1/notifications/push-token',
      data: {
        'token': token,
        'platform': platform,
        'device_name': deviceName ?? 'Flutter Device',
      },
      options: Options(
        headers: {'Authorization': 'Bearer $_accessToken'},
      ),
    );
  }

  Future<void> unregisterPushToken() async {
    final token = await FirebaseMessaging.instance.getToken();
    if (token == null || token.isEmpty) return;

    await _dio.delete(
      '$_baseUrl/api/v1/notifications/push-token',
      data: {'token': token},
      options: Options(
        headers: {'Authorization': 'Bearer $_accessToken'},
      ),
    );
  }
}
```

---

## 6) Handle Token Refresh

FCM token can rotate. Always update backend when it changes:

```dart
StreamSubscription<String>? tokenRefreshSub;

void listenTokenRefresh(PushApiService pushApiService) {
  tokenRefreshSub = FirebaseMessaging.instance.onTokenRefresh.listen((token) async {
    await pushApiService.registerPushToken();
  });
}
```

Dispose it on app shutdown/logout.

---

## 7) Handle Foreground Notifications

```dart
void listenForegroundMessages() {
  FirebaseMessaging.onMessage.listen((RemoteMessage message) {
    final title = message.notification?.title ?? 'Notification';
    final body = message.notification?.body ?? '';
    // Show local notification via flutter_local_notifications
    print('Foreground push: $title - $body');
  });
}
```

---

## 8) Handle Notification Taps (Deep Link)

Backend sends data keys like:
- `kind` (`chat_message` or `system_notification`)
- `conversation_id`, `message_id` (for chat)
- `notification_id` (for system notifications)

Use these keys to open correct screen:

```dart
void setupPushNavigation(void Function(String route, Map<String, dynamic> args) navigate) {
  FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
    final data = message.data;
    final kind = data['kind'];

    if (kind == 'chat_message') {
      navigate('/chat', {'conversationId': data['conversation_id']});
      return;
    }

    if (kind == 'system_notification') {
      navigate('/notifications', {'notificationId': data['notification_id']});
      return;
    }

    navigate('/notifications', {});
  });
}
```

Also check initial message for cold start:

```dart
Future<void> handleInitialMessage(
  void Function(String route, Map<String, dynamic> args) navigate,
) async {
  final initialMessage = await FirebaseMessaging.instance.getInitialMessage();
  if (initialMessage == null) return;

  final data = initialMessage.data;
  if (data['kind'] == 'chat_message') {
    navigate('/chat', {'conversationId': data['conversation_id']});
  } else {
    navigate('/notifications', {});
  }
}
```

---

## 9) App Startup Order (Recommended)

On app launch:

1. `await initFirebase()`
2. Load auth token from secure storage
3. If user logged in:
   - `registerPushToken()`
   - `listenTokenRefresh(...)`
   - `listenForegroundMessages()`
   - `setupPushNavigation(...)`
   - `handleInitialMessage(...)`

This ensures token sync and deep-link routing are active from first frame.

---

## 10) Logout Best Practice

On logout:
1. Call backend `DELETE /api/v1/notifications/push-token`
2. Cancel token refresh listener
3. Clear local auth/session data

---

## 11) Server Environment Checklist

Backend `.env` should include:

```env
FIREBASE_CREDENTIALS=storage/keys/suganta-tutors-firebase-adminsdk-fbsvc-51a7fa7774.json
# FIREBASE_PROJECT_ID=your-project-id   # optional
```

Notes:
- This project already defaults to `storage/keys/suganta-tutors-firebase-adminsdk-fbsvc-51a7fa7774.json` in config.
- `FIREBASE_PROJECT_ID` is optional because project ID can be resolved from the service account JSON.
- Keep this JSON file private and never expose it to Flutter/mobile client code.

Then:

```bash
php artisan config:clear
```

---

## 12) Quick Testing Flow

1. Login from Flutter app
2. Register push token using API
3. Send chat message from another user
4. Verify receiver gets push
5. Trigger any backend user notification
6. Verify push arrives and deep link opens correct screen

---

## 13) Suggested Flutter File Structure

```text
lib/
  services/
    push_api_service.dart
    push_notification_service.dart
  core/
    navigation/
      app_router.dart
```

- `push_api_service.dart`: calls backend register/remove token endpoints
- `push_notification_service.dart`: firebase listeners + local notification display + deep-link dispatch
- router layer: maps payload (`kind`, `conversation_id`, `notification_id`) to screens

---

## 14) Payload Mapping Used by Backend

### Chat message push
- `kind`: `chat_message`
- `conversation_id`
- `message_id`
- `sender_id`

### System notification push
- `kind`: `system_notification`
- `notification_id`
- `type`
- `priority`
- `action_url` (optional)

