# 🌿 Native Flora Detection and Management System

### Using Computer Vision and Geoanalytics in Talaingod, Davao del Norte

The **Native Flora Detection and Management System** is a capstone project that uses **computer vision, GPS, geoanalytics, and cloud-based data management** to help identify, document, map, and monitor selected native flora species in Talaingod, Davao del Norte.

The system consists of an **Android mobile application developed using Flutter** for students and a **PHP-based web application** for forestry experts and environmental evaluators.

## 🌱 Selected Native Flora

The system focuses on five selected native flora species:

- **Anahaw** — *Saribus rotundifolius*
- **Narra** — *Pterocarpus indicus*
- **Molave** — *Vitex parviflora*
- **Bangkal** — *Nauclea orientalis*
- **Talisay** — *Terminalia catappa*

## 📱 Mobile Application

The Flutter Android application is primarily designed for students. It allows users to:

- Scan flora using the live camera
- Detect and classify flora using computer vision
- View the detection confidence score
- Capture and photo-tag flora
- Record GPS coordinates
- View detected flora on a map
- View scan history
- View the Species Guide and taxonomy information

The system can use the selected **YOLO-based or CNN-based computer vision model**, depending on the model being evaluated or used by the system.

## 💻 Web-Based Monitoring System

The PHP web application is intended for **forestry experts/environmental evaluators**, who also serve as the system administrators.

They can:

- Review submitted flora records
- Accept, reject, or reclassify detection results
- View flora locations through the map
- Manage users and flora records
- Monitor collected data
- View reports and analytics

## 🔥 Technologies Used

- **Flutter / Dart** — Android mobile application
- **PHP** — Web-based monitoring system
- **Firebase Authentication** — User authentication
- **Firebase Realtime Database** — Data storage and synchronization
- **Firebase Cloud Storage** — Image storage
- **Computer Vision** — Flora detection and classification
- **YOLO / CNN** — Detection and classification models
- **GPS & Geoanalytics** — Location tracking and map visualization

## 🔄 System Flow

```text
Student
   ↓
Flutter Mobile App
   ↓
Camera / Image Capture
   ↓
Computer Vision (YOLO / CNN)
   ↓
Flora Detection & Classification
   ↓
GPS + Photo Tagging
   ↓
Firebase
   ↓
PHP Web Monitoring System
   ↓
Forestry Expert / Administrator
   ↓
Accept / Reject / Reclassify
   ↓
Monitoring & Map Visualization
