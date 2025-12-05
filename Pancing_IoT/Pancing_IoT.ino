#include <WiFi.h>
#include "HX711.h"
#include <HTTPClient.h>

// -------------------------
// WiFi Hotspot Kamu
// -------------------------
const char* ssid     = "ZAKY";
const char* password = "zaky1234";

// -------------------------
// Pin HX711 (Loadcell)
// -------------------------
#define LOADCELL_DOUT_PIN 5
#define LOADCELL_SCK_PIN  18

HX711 scale;

// -------------------------
// Pin Ultrasonic & Tombol
// -------------------------
#define TRIG_PIN    12
#define ECHO_PIN    14
#define BUTTON_PIN  27   // tombol ke GND, pakai INPUT_PULLUP

// -------------------------
// Kalibrasi Loadcell
// -------------------------
float known_weight        = 1000;   
float calibration_factor  = 1;

const char* serverURL = "http://192.168.1.100/ikan_nila/receive_loadcell.php";

const char* NAMA_PESERTA = "Budi Santoso";

float currentBerat    = 0;   
bool  lastButtonState = HIGH;

void setup() {
  Serial.begin(115200);
  delay(2000);

  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  pinMode(BUTTON_PIN, INPUT_PULLUP);

  // ======= WiFi =======
  Serial.println("\n\n=== SISTEM IOT LOADCELL + ULTRASONIC ===");
  Serial.println("Menghubungkan ke WiFi...");
  WiFi.begin(ssid, password);

  int retry = 0;
  while (WiFi.status() != WL_CONNECTED && retry < 20) {
    delay(500);
    Serial.print(".");
    retry++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✓ WiFi terhubung!");
    Serial.print("✓ IP ESP32: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\n✗ Gagal koneksi WiFi!");
  }

  Serial.println("\nSetup HX711...");
  scale.begin(LOADCELL_DOUT_PIN, LOADCELL_SCK_PIN);

  Serial.println("Melakukan kalibrasi...");
  scale.set_scale();
  scale.tare();

  long reading = scale.get_units(10);
  calibration_factor = reading / known_weight;
  scale.set_scale(calibration_factor);

  Serial.println("✓ Kalibrasi selesai!");
  Serial.println("✓ Sistem siap!\n");
}


float readUltrasonicCm() {
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);

  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);

  long duration = pulseIn(ECHO_PIN, HIGH, 30000); 

  if (duration == 0) {
    return -1; 
  }

  float distance_cm = duration * 0.034 / 2.0;
  return distance_cm;
}


void kirimDataKeServer(float berat, float jarak_cm) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("✗ WiFi tidak terhubung, tidak bisa kirim data!");
    return;
  }

  Serial.print("📤 Mengirim ke: ");
  Serial.println(serverURL);


  String jsonPayload = "{";
  jsonPayload += "\"berat_kg\":"   + String(berat, 2)      + ",";
  jsonPayload += "\"jarak_cm\":"   + String(jarak_cm, 2)   + ",";
  jsonPayload += "\"time\":"  + String(millis())      + ",";
  jsonPayload += "\"nama_peserta\":\"" + String(NAMA_PESERTA) + "\"";
  jsonPayload += "}";

  Serial.print("📦 JSON: ");
  Serial.println(jsonPayload);

  WiFiClient client;
  HTTPClient http;
  http.begin(client, serverURL);
  http.addHeader("Content-Type", "application/json");

  int httpResponseCode = http.POST(jsonPayload);

  if (httpResponseCode > 0) {
    Serial.print("✓ Response code: ");
    Serial.println(httpResponseCode);
    String response = http.getString();
    Serial.print("📨 Respon server: ");
    Serial.println(response);
  } else {
    Serial.print("✗ Error code: ");
    Serial.println(httpResponseCode);
    Serial.print("✗ Error: ");
    Serial.println(http.errorToString(httpResponseCode));
  }

  http.end();
}

void loop() {
  // cek WiFi
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("⚠ WiFi terputus, mencoba reconnect...");
    WiFi.reconnect();
    delay(2000);
  }

  // baca loadcell
  if (scale.is_ready()) {
    float berat = scale.get_units(5);

    if (berat < 0.1) {
      berat = 0;
    }

    currentBerat = berat;

    Serial.print("[");
    Serial.print(millis() / 1000);
    Serial.print("s] Berat: ");
    Serial.print(currentBerat, 2);
    Serial.println(" kg");
  } else {
    Serial.println("✗ Loadcell belum siap!");
  }

  bool buttonState = digitalRead(BUTTON_PIN);
  if (lastButtonState == HIGH && buttonState == LOW) {
    Serial.println("🔘 Tombol ditekan, membaca ultrasonic & mengirim data...");

    float jarak_cm = readUltrasonicCm();
    if (jarak_cm < 0) {
      Serial.println("✗ Ultrasonic gagal baca jarak!");
    } else {
      Serial.print("📏 Jarak Ultrasonic: ");
      Serial.print(jarak_cm, 2);
      Serial.println(" cm");

      kirimDataKeServer(currentBerat, jarak_cm);
    }

    delay(300); 
  }

  lastButtonState = buttonState;

  delay(200);
}
