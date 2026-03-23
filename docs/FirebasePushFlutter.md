# Firebase Push Notifications (Flutter)

This guide explains how to integrate Firebase Cloud Messaging (FCM) in Flutter with the backend APIs added in this project.

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

## 4) Register Token to API

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

## 5) Handle Token Refresh

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

## 6) Handle Foreground Notifications

```dart
void listenForegroundMessages() {
  FirebaseMessaging.onMessage.listen((RemoteMessage message) {
    // Show local notification/banner/snackbar as needed
    final title = message.notification?.title ?? 'Notification';
    final body = message.notification?.body ?? '';
    // TODO: integrate flutter_local_notifications if needed
    print('Foreground push: $title - $body');
  });
}
```

---

## 7) Handle Notification Taps (Deep Link)

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

## 8) Logout Best Practice

On logout:
1. Call backend `DELETE /api/v1/notifications/push-token`
2. Cancel token refresh listener
3. Clear local auth/session data

---

## 9) Server Environment Checklist

Backend `.env` must include:

```env
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_CREDENTIALS=C:\absolute\path\to\firebase-service-account.json
```

Then:

```bash
php artisan config:clear
```

---

## 10) Quick Testing Flow

1. Login from Flutter app
2. Register push token using API
3. Send chat message from another user
4. Verify receiver gets push
5. Trigger any backend user notification
6. Verify push arrives and deep link opens correct screen

