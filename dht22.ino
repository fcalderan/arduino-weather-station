#include <DHT22.h>
#include <SPI.h>
#include <Ethernet.h>
#include <LiquidCrystal.h>


// Data wire is plugged into port 6 on the Arduino
// Connect a 4.7K resistor between VCC and the data pin (strong pullup)
#define DHT22_PIN 6

int RSpin = 7;
int Enablepin = 8;
int D4pin = 9;
int D5pin = 3;
int D6pin = 4;
int D7pin = 5;

// Setup a DHT22 instance
DHT22 myDHT22(DHT22_PIN);


// Enter a MAC address for your controller below.
// Newer Ethernet shields have a MAC address printed on a sticker on the shield
byte mac[] = { 0x90, 0xA2, 0xDA, 0x0F, 0x07, 0xFE };

IPAddress ip(192,168,0,2);
char server[] = "192.168.0.1";    
EthernetClient client;

boolean lastConnected = false;                 // state of the connection last time through the main loop
unsigned long lastConnectionTime = 0;          // last time you connected to the server, in milliseconds
const unsigned long postingInterval = 20 * 1000;  // delay between updates, in milliseconds

unsigned long lastSensorValues = 0;                 // state of the connection last time through the main loop
const unsigned long sensorInterval = 10 * 1000;  // delay between sensor values, in milliseconds


// initialize the library with the numbers of the interface pins  
LiquidCrystal lcd(RSpin, Enablepin, D4pin, D5pin, D6pin, D7pin);
  
String tmp = "";
String hum = "";




void setup(void) {

   // start serial port
  Serial.begin(9600);  
  lcd.begin(20, 4);
  lcd.print("DHT22 Sensor");
  delay(3000);
  
}

void httpRequest(String t, String h) {

  Ethernet.begin(mac, ip);
  delay(1000);
  
  String query = "t=" + t + "&h=" + h + "&i=" + postingInterval/1000;

  if (client.connect(server, 80)) {
    Serial.println("connecting...");
    // send the HTTP PUT request:
    client.println("POST /arduino-weather-station/dht22.php HTTP/1.1");
    client.println("Host: 192.168.0.1");
    client.println("Content-Type: application/x-www-form-urlencoded");
    client.println("Connection: close");
    client.println("User-Agent: arduino/1.0");
    client.print("Content-Length: ");
    client.println(query.length());
    client.println();
    client.println(query);
    client.println();

  }
  else {
    // if you couldn't make a connection:
    Serial.println("connection failed");
    Serial.println("disconnecting.");
    client.stop();
  }

  // note the time that the connection was made:
  lastConnectionTime = millis();

}

void getSensorValues(void) {
  
  String tmpString = "";
  String humString = "";

  DHT22_ERROR_t errorCode;
  
  errorCode = myDHT22.readData();
  switch(errorCode)
  {
    case DHT_ERROR_NONE:
      Serial.print("Got Data ");
      Serial.print(myDHT22.getTemperatureC());
      Serial.print("C ");
      Serial.print(myDHT22.getHumidity());
      Serial.println("%");
      // Alternately, with integer formatting which is clumsier but more compact to store and
      // can be compared reliably for equality:
       
      char buf[128]; 
      
      sprintf(buf, "Integer-only reading: Temperature %hi.%01hi C, Humidity %i.%01i %% RH",
                  myDHT22.getTemperatureCInt()/10, abs(myDHT22.getTemperatureCInt()%10),
                  myDHT22.getHumidityInt()/10, myDHT22.getHumidityInt()%10);
      Serial.println(buf);
      
   
      lcd.setCursor(0, 1);
      //sprintf(tmp, "Temp:     %hi.%01hi C", myDHT22.getTemperatureCInt()/10, abs(myDHT22.getTemperatureCInt()%10));
      tmp = String(myDHT22.getTemperatureCInt()/10) + "." + String(abs(myDHT22.getTemperatureCInt()%10));
      tmpString = "Temp:     " + tmp  + " C";
      lcd.print(tmpString);
      
      lcd.setCursor(0, 2);
      //sprintf(hum, "Humidity: %i.%01i %%",  myDHT22.getHumidityInt()/10, myDHT22.getHumidityInt()%10);
      hum = String(myDHT22.getHumidityInt()/10) + "." + String(myDHT22.getHumidityInt()%10);
      humString = "Humidity: " + hum + " %";     
      lcd.print(humString);
      
      break;
      
    case DHT_ERROR_CHECKSUM:
      Serial.print("check sum error ");
      Serial.print(myDHT22.getTemperatureC());
      Serial.print("C ");
      Serial.print(myDHT22.getHumidity());
      Serial.println("%");
      break;
      
    case DHT_BUS_HUNG:
      Serial.println("BUS Hung ");
      break;
      
    case DHT_ERROR_NOT_PRESENT:
      Serial.println("Not Present ");
      break;
      
    case DHT_ERROR_ACK_TOO_LONG:
      Serial.println("ACK time out ");
      break;
      
    case DHT_ERROR_SYNC_TIMEOUT:
      Serial.println("Sync Timeout ");
      break;
      
    case DHT_ERROR_DATA_TIMEOUT:
      Serial.println("Data Timeout ");
      break;
      
    case DHT_ERROR_TOOQUICK:
      Serial.println("Polled to quick ");
      break;
    }
    
    lastSensorValues = millis(); 
}


void loop(void) { 
  

   if (client.available()) {
       char c = client.read();
       Serial.print(c);
   }

   // if there's no net connection, but there was one last time
   // through the loop, then stop the client:
   if (!client.connected() && lastConnected) {
       Serial.println();
       Serial.println("disconnecting.");
       client.stop();
   }

  
   if(millis() - lastSensorValues > sensorInterval) {      
        getSensorValues();
   }
   

   // if you're not connected, and ten seconds have passed since
   // your last connection, then connect again and send data:
   if(!client.connected() && (millis() - lastConnectionTime > postingInterval)) {
       httpRequest(tmp, hum);
   }
   
   // store the state of the connection for next time through
   // the loop:
   lastConnected = client.connected();
}

 
