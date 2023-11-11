## Original project here : https://medium.com/@AnyangweChe/saving-gps-location-to-database-from-android-fused-location-provider-api-54a6cd74355e

1. ### Download WAMP

- https://sourceforge.net/projects/wampserver/

2. ### Create MySQL Database and Tables

```SQL
CREATE TABLE  `realtimedb`.`realtime` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`uniqueId` VARCHAR( 20 ) NOT NULL ,
`latitude` VARCHAR( 20 ) NOT NULL ,
`longitude` VARCHAR( 20 ) NOT NULL ,
`dateCreated` DATETIME NOT NULL
) ENGINE = INNODB;
```

3. ### Creating PHP Project

   1. Go into www folder and create a folder named realtimelocation. This will be the root directory of our project.
   2. In realtimelocation, create another directory named include. In this folder, we keep all the helper classes.
   3. Now inside include, create a php file named dbConnect.php and add below content. In this class we handle opening and closing of database connection. Replace the DB_USER and DB_PASSWORD values with your’s.

   ```PHP
   define('HOST','localhost');
   define('USER','root');
   define('PASS','');
   define('DB','realtimedb');

   class DBConnect {
   private $conn;

   // Connecting to database
   public function connect() {

   // Connecting to mysql database
   $this->conn = mysqli_connect(HOST,USER,PASS,DB) or die('Unable to Connect');

   // return database handler
   return $this->conn;
       }
   }
   ```

   4. Create dbFunctions.php inside include with below content. This file contains functions to store location data into database.

```PHP
    class DBFunction {
    private $conn;

    // constructor
    function __construct() {
    require_once 'dbConnect.php';
    // connecting to database
    $db = new DBConnect();
    $this->conn = $db->connect();
    }

    /**
    * Storing user location
    * returns true for sucessfull update
    * false for unsucessfull update
    */
    public function addLocation($uniqueId, $latitude, $longitude) {

    $stmt = $this->conn->prepare(“INSERT INTO realtime(uniqueId, latitude, longitude, dateCreated) VALUES(?, ?, ?, NOW())”);
    $stmt->bind_param("sss", $uniqueId, $latitude, $longitude);
    $result = $stmt->execute();
    $stmt->close();

    // check for successful store
    if($result === true){
    return true;
    }else{
    return false;
        }
    }
}
```

3.1 Registration Endpoint

- Now we have all the required classes ready. Let’s start creating the endpoint for user registration. This endpoint accepts uniqueId, latitude and longitude as POST parameters and store the users location data in MySQL database.

  1.  In realtimelocation root directory, create addlocation.php and below code.

```PHP
require_once 'include/dbFunctions.php';
$db = new DBFunction();

// json response array
$response = array();
if (isset($_POST['uniqueId']) && isset($_POST['latitude']) && isset($_POST['longitude'])) {

// receiving the post params
$uniqueId = $_POST['uniqueId'];
$latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];

// add new position to db
$location = $db->addLocation($uniqueId, $latitude, $longitude);

if ($location) {
$response["error"] = true;
echo json_encode($response);
}else{
$response["error"] = false;
echo json_encode($response);
}
}
```

3.2 Types of JSON Responses
The following are the different types of JSON responses for registration and login endpoints.

3.3.1 Adding Location

```js
URL: http://localhost/realtimelocation/addlocation.php

PARAMS: uniqueId, latitude, longitude

Success response

{
"error": true
}

Failure response

{
"error": false
}
```

Now we have completed the PHP part. Let’s start the android part.

See php project on github

4. ### Creating the Android Project

   The app we’re gonna build will have two simple screens Realtime Fused Location Screen and Realtime Location Screen.

1. In Android Studio

- create a new project from File ⇒ New Project and fill all the required details.

2.  Open build.gradle and add volley library support by adding

        implementation ‘com.android.volley:volley:1.1.0’
        implementation ‘com.google.android.gms:play-services-location:17.0.0’

    under dependencies.

        implementation 'androidx.recyclerview:recyclerview:1.0.0-beta01'
        implementation 'androidx.cardview:cardview:1.0.0-beta01'
        // location play services
        implementation 'com.google.android.gms:play-services-location:17.0.0'
        // volley
        implementation 'com.android.volley:volley:1.1.0'

3.  Open strings.xml located under res ⇒ values and add below string values.

```XML
<resources>
    <string name="app_name">Realtime Fused Location</string>
    <string name="location_label">Realtime Location</string>
    <string name="loading">Loading…</string>
    <string name="latitude">Latitude</string>
    <string name="longitude">Longitude</string>
    <string name="novalues">0.0</string>
    <string name="metagetGPStxt">Get GPS From Device</string>
    <string name="realtimegetGPStxt">Real Time Location</string>
    <string name="infotext">Get real time position from deveice and send to a server witnin a 30 seconds interval. Receive acknowledgement from server when position is received</string>
</resources>
```

1. Now open AndroidManifest.xml and add

- INTERNET permission, ACCESS_COARSE_LOCATION permision and
- ACCESS_FINE_LOCATION permission

Also add other activities (RealtimeActivity and MainActivity) which we are going to create shortly.

```XML
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android"
    package="tafieldscience.realtimefusedlocation">

    <uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
    <uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
    <uses-permission android:name="android.permission.INTERNET" />

    <application
        android:allowBackup="true"
        android:icon="@mipmap/ic_launcher"
        android:label="@string/app_name"
        android:roundIcon="@mipmap/ic_launcher_round"
        android:supportsRtl="true"
        android:theme="@style/AppTheme">
        <activity android:name=".RealtimeActivity"
            android:label="@string/location_label">
            <meta-data
                android:name="android.support.PARENT_ACTIVITY"
                android:value="tafieldscience.realtimefusedlocation.MainActivity" />
        </activity>
        <activity android:name=".MainActivity">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />

                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>
    </application>

</manifest>
```

4.1 Adding the Main Screen

1. Create an xml file named activity_main.xml under res ⇒ layout.

```XML
<?xml version="1.0" encoding="utf-8"?>
<androidx.constraintlayout.widget.ConstraintLayout xmlns:android="http://schemas.android.com/apk/res/android"
    xmlns:app="http://schemas.android.com/apk/res-auto"
    xmlns:tools="http://schemas.android.com/tools"
    android:layout_width="match_parent"
    android:layout_height="match_parent"
    tools:context=".MainActivity">

    <LinearLayout
        android:orientation="vertical"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:paddingBottom="16dp"
        android:paddingLeft="10dp"
        android:paddingRight="10dp"
        app:layout_constraintLeft_toLeftOf="parent"
        app:layout_constraintRight_toRightOf="parent"
        app:layout_constraintTop_toTopOf="parent"
        app:layout_constraintTop_toBottomOf="parent">

    <Button
        android:id="@+id/metagetGPS"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        android:background="@color/colorPrimary"
        android:layout_marginTop="16dp"
        android:layout_marginBottom="16dp"
        android:text="@string/metagetGPStxt"
        android:textSize="7pt"
        android:textStyle="normal"
        android:textColor="@android:color/white" />

        <androidx.cardview.widget.CardView
            android:id="@+id/list_cardview"
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            app:cardBackgroundColor="@android:color/white"
            app:cardCornerRadius="2dp"
            app:cardElevation="4dp"
            android:foreground="?attr/selectableItemBackground">

            <LinearLayout
                android:layout_width="match_parent"
                android:layout_height="wrap_content"
                android:orientation="vertical">

                <LinearLayout
                    android:layout_width="match_parent"
                    android:layout_height="wrap_content"
                    android:layout_gravity="center_vertical"
                    android:paddingTop="8dp"
                    android:paddingLeft="10dp">

                    <TextView
                        android:layout_width="0dp"
                        android:layout_height="wrap_content"
                        android:layout_weight="1"
                        android:text="@string/latitude"
                        android:textStyle="normal"
                        android:paddingBottom="4dp"
                        android:textSize="14sp" />

                    <TextView
                        android:id="@+id/latitude"
                        android:layout_width="0dp"
                        android:layout_height="wrap_content"
                        android:layout_weight="2"
                        android:textSize="15sp"
                        android:textStyle="bold"
                        android:textAppearance="?attr/textAppearanceListItem"
                        android:text="@string/novalues"/>

                </LinearLayout>

                <RelativeLayout
                    android:id="@+id/category_line_layout"
                    android:layout_width="wrap_content"
                    android:layout_height="wrap_content"
                    android:orientation="vertical">
                    <View
                        android:id="@+id/verticalLine"
                        android:layout_width="match_parent"
                        android:layout_height="1dip"
                        android:layout_marginTop="8dp"
                        android:layout_marginBottom="8dp"
                        android:background="#e9e8e8" />
                </RelativeLayout>

                <LinearLayout
                    android:layout_width="match_parent"
                    android:layout_height="wrap_content"
                    android:layout_gravity="center_vertical"
                    android:paddingTop="2dp"
                    android:paddingBottom="10dp"
                    android:paddingLeft="10dp">

                    <TextView
                        android:layout_width="0dp"
                        android:layout_height="wrap_content"
                        android:layout_weight="1"
                        android:text="@string/longitude"
                        android:textStyle="normal"
                        android:paddingBottom="8dp"
                        android:textSize="14sp" />

                    <TextView
                        android:id="@+id/longitude"
                        android:layout_width="0dp"
                        android:layout_height="wrap_content"
                        android:layout_weight="2"
                        android:textSize="15sp"
                        android:textStyle="bold"
                        android:textAppearance="?attr/textAppearanceListItem"
                        android:text="@string/novalues"/>

                </LinearLayout>

            </LinearLayout>

        </androidx.cardview.widget.CardView>

        <TextView
            android:id="@+id/realtimetxt"
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:text="@string/infotext"
            android:textStyle="normal"
            android:paddingBottom="8dp"
            android:paddingTop="32dp"
            android:textSize="14sp" />

        <Button
            android:id="@+id/realtimegetGPS"
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:background="@color/colorPrimary"
            android:layout_marginTop="16dp"
            android:layout_marginBottom="16dp"
            android:text="@string/realtimegetGPStxt"
            android:textSize="7pt"
            android:textStyle="normal"
            android:textColor="@android:color/white" />

    </LinearLayout>

</androidx.constraintlayout.widget.ConstraintLayout>
```

6. ### Create an activity class named MainActivity.java under activity package.

In this class

- `connectGoogleClient()` — Method verifies google play service and connects GoogleApiClient

- `requestLocationUpdate() `
  starts location updates with sLocationRequest as the location request object and sLocationCallback object to get results of the location request. These objects are initialized when GoogleApiClient is connected i.e in the connected callback of GoogleApiClient.

- `buildGoogleApiClient()` - Method builds the GoogleApiClient object and adds the ConnectionCallbacks ( where sLocationRequest and sLocationCallback are initialized), the OnConnectionFailedListener, the api we want to use i.e the Location Api and build. We call connectGoogleClient() method to connect.

- `locationSettingsRequest()` — Method builds the location settings and adds a location request. The method checks location service settings. if location service is not activated, in the OnFailureListener callback, a ResolvableApiException with startResolutionForResult is started which shows a dialog to enable location service. Response from the dialog is handled in onActivityResult. If location service is activated, requestLocationUpdate() is called in the OnSuccessListener callback of the SettingClient

Below is the full code.

```JAVA
package tafieldscience.realtimefusedlocation;

import android.app.Activity;
import android.content.Intent;
import android.content.IntentSender;
import android.content.pm.PackageManager;
import android.os.Bundle;
import android.os.Looper;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

import com.google.android.gms.common.ConnectionResult;
import com.google.android.gms.common.GoogleApiAvailability;
import com.google.android.gms.common.api.ApiException;
import com.google.android.gms.common.api.GoogleApiClient;
import com.google.android.gms.common.api.ResolvableApiException;
import com.google.android.gms.location.FusedLocationProviderClient;
import com.google.android.gms.location.LocationCallback;
import com.google.android.gms.location.LocationRequest;
import com.google.android.gms.location.LocationResult;
import com.google.android.gms.location.LocationServices;
import com.google.android.gms.location.LocationSettingsRequest;
import com.google.android.gms.location.LocationSettingsStatusCodes;
import com.google.android.gms.location.SettingsClient;
import com.google.android.gms.tasks.OnCanceledListener;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;

//import android.support.v7.app.AppCompatActivity;

public class MainActivity extends AppCompatActivity {

    private FusedLocationProviderClient sFusedLocationClient;

    private LocationCallback sLocationCallback;
    private LocationRequest sLocationRequest;
    private GoogleApiClient sGoogleApiClient;
    private boolean apiconnectionstatus = false ;
    private long interval = 10000;
    private long fastestInterval = 5000;
    private int priority = LocationRequest.PRIORITY_BALANCED_POWER_ACCURACY ;
    private int numberOfUpdates;
    private double Latitude = 0.0, Longitude = 0.0;
    private static final String TAG = "MainActivity";
    Button realtimegetGPS,metagetGPS ;
    TextView longitude,latitude ;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        realtimegetGPS = findViewById(R.id.realtimegetGPS);
        metagetGPS = findViewById(R.id.metagetGPS);
        latitude = findViewById(R.id.latitude);
        longitude = findViewById(R.id.longitude);

        realtimegetGPS.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View view) {
                // Start Realtime Activity
                Intent i = new Intent(MainActivity.this, RealtimeActivity.class);
                startActivity(i);
            }
        });

        metagetGPS.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View view) {
                if(apiconnectionstatus) {
                    locationSettingsRequest();
                }
            }
        });
    }

    /**
     * Function to connect googleapiclient
     * */
    private void connectGoogleClient() {
        GoogleApiAvailability googleAPI = GoogleApiAvailability.getInstance();
        int resultCode = googleAPI.isGooglePlayServicesAvailable(this);
        if (resultCode == ConnectionResult.SUCCESS) {
            sGoogleApiClient.connect();
        } else {
            int REQUEST_GOOGLE_PLAY_SERVICE = 988;
            googleAPI.getErrorDialog(this, resultCode, REQUEST_GOOGLE_PLAY_SERVICE);
        }
    }

    /**
     * Function to start FusedLocation updates
     */
    public void requestLocationUpdate() {
        if (ContextCompat.checkSelfPermission(getApplicationContext(), android.Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED && ContextCompat.checkSelfPermission(getApplicationContext(), android.Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED) {
            latitude.setText(getString(R.string.loading));
            longitude.setText(getString(R.string.loading));
            sFusedLocationClient.requestLocationUpdates(sLocationRequest, sLocationCallback, Looper.myLooper());
        }
    }

    /**
     * Build GoogleApiClient and connect
     */
    private synchronized void buildGoogleApiClient() {
        sFusedLocationClient = LocationServices.getFusedLocationProviderClient(this);

        sGoogleApiClient = new GoogleApiClient.Builder(this)
                .addConnectionCallbacks(new GoogleApiClient.ConnectionCallbacks() {
                    @Override
                    public void onConnected(@Nullable Bundle bundle) {

                        // Creating a location request
                        sLocationRequest = new LocationRequest();
                        sLocationRequest.setPriority(priority);
                        sLocationRequest.setSmallestDisplacement(0);
                        sLocationRequest.setNumUpdates(1);

                        // FusedLocation callback
                        sLocationCallback = new LocationCallback() {
                            @Override
                            public void onLocationResult(final LocationResult locationResult) {
                                super.onLocationResult(locationResult);

                                Latitude = locationResult.getLastLocation().getLatitude();
                                Longitude = locationResult.getLastLocation().getLongitude();

                                if (Latitude == 0.0 && Longitude == 0.0) {
                                    requestLocationUpdate();
                                } else {
                                    // Update Textview
                                    latitude.setText(Double.toString(Latitude));
                                    longitude.setText(Double.toString(Longitude));
                                }
                            }
                        };

                        // Simple api status check
                        apiconnectionstatus = true ;
                    }

                    @Override
                    public void onConnectionSuspended(int i) {
                        connectGoogleClient();
                    }
                })
                .addOnConnectionFailedListener(new GoogleApiClient.OnConnectionFailedListener() {
                    @Override
                    public void onConnectionFailed(@NonNull ConnectionResult connectionResult) {

                    }
                })
                .addApi(LocationServices.API)
                .build();

        // Connect googleapiclient after build
        connectGoogleClient();
    }

    /**
     * Function to request Location permission and enable GPS Dialog
     */
    private void locationSettingsRequest(){
        SettingsClient mSettingsClient = LocationServices.getSettingsClient(this);
        LocationSettingsRequest.Builder builder = new LocationSettingsRequest.Builder();
        builder.addLocationRequest(sLocationRequest);
        builder.setAlwaysShow(true);
        LocationSettingsRequest mLocationSettingsRequest = builder.build();

        mSettingsClient
                .checkLocationSettings(mLocationSettingsRequest)
                .addOnSuccessListener(locationSettingsResponse -> {
                    // Start FusedLocation if GPS is enabled
                    requestLocationUpdate();
                })
                .addOnFailureListener(e -> {
                    // Show enable GPS Dialog and handle dialog buttons
                    int statusCode = ((ApiException) e).getStatusCode();
                    switch (statusCode) {
                        case LocationSettingsStatusCodes.RESOLUTION_REQUIRED:
                            try {
                                int REQUEST_CHECK_SETTINGS = 214;
                                ResolvableApiException rae = (ResolvableApiException) e;
                                rae.startResolutionForResult(MainActivity.this, REQUEST_CHECK_SETTINGS);
                            } catch (IntentSender.SendIntentException sie) {
                                showLog("Unable to Execute Request");
                            }
                            break;
                        case LocationSettingsStatusCodes.SETTINGS_CHANGE_UNAVAILABLE:
                            showLog("Location Settings are Inadequate, and Cannot be fixed here. Fix in Settings");
                    }
                })
                .addOnCanceledListener(new OnCanceledListener() {
                    @Override
                    public void onCanceled() {
                        showLog("Canceled No Thanks");
                    }
                });
    }

    private void showLog(String message) {
        Log.e(TAG, "" + message);
    }

    @Override
    public void onResume(){
        super.onResume();
        buildGoogleApiClient();
    }

    @Override
    public void onDestroy(){
        super.onDestroy();
        sFusedLocationClient.removeLocationUpdates(sLocationCallback);
    }

    // Handle results of enable GPS Dialog
    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (requestCode == 214) {
            switch (resultCode){
                case Activity.RESULT_OK:
                {
                    // User enabled GPS start fusedlocation
                    requestLocationUpdate();
                    break;
                }
                case Activity.RESULT_CANCELED:
                {
                    // The user was asked to change settings, but chose not to
                    Toast.makeText(getApplication(), "Location not enabled, user cancelled.", Toast.LENGTH_LONG).show();
                    break;
                }
                default:
                {
                    break;
                }
            }
        }
    }
}
```

### Adding the Realtime Location Screen

7. Create an xml layout named **activity_realtime.xml** under res ⇒ layout.

```XML
<?xml version="1.0" encoding="utf-8"?>
<androidx.constraintlayout.widget.ConstraintLayout xmlns:android="http://schemas.android.com/apk/res/android"
    xmlns:app="http://schemas.android.com/apk/res-auto"
    xmlns:tools="http://schemas.android.com/tools"
    android:layout_width="match_parent"
    android:layout_height="match_parent"
    tools:context=".RealtimeActivity">

    <ListView
        android:id="@+id/listing_items"
        android:layout_width="match_parent"
        android:layout_height="wrap_content"
        app:layout_constraintLeft_toLeftOf="parent"
        app:layout_constraintRight_toRightOf="parent"
        app:layout_constraintTop_toTopOf="parent"
        app:layout_constraintTop_toBottomOf="parent">
    </ListView>

</androidx.constraintlayout.widget.ConstraintLayout>
```

8. Create another xml layout named list_layout.xml under res ⇒ layout for the listview

```XML
<?xml version="1.0" encoding="utf-8"?>
<TextView xmlns:android="http://schemas.android.com/apk/res/android"
    android:id="@+id/list_text"
    android:layout_width="match_parent"
    android:layout_height="wrap_content"
    android:layout_weight="2"
    android:textSize="15sp"
    android:padding="10dp"
    android:textAppearance="?attr/textAppearanceListItem">
</TextView>
```

9. Create an activity class named **RealtimeActivity.java** under activity package.

In this class

The url for the server is “http://192.168.42.85/RealtimeLocation/addlocation.php" replace the ip address with that of your server or with your domain name

The unique identifier for `app installation UUID.randomUUID().toString()` This identifier is used to store location update for each request sent to the server.

`connectGoogleClient()` — Method verifies google play service and connects GoogleApiClient

`requestLocationUpdate()` — Method starts location updates with mLocationRequest as the location request object and mLocationCallback object to get results of the location request. These objects are initialized when GoogleApiClient is connected i.e in the connected callback of GoogleApiClient.

`buildGoogleApiClient()` — Method builds the GoogleApiClient object and adds the ConnectionCallbacks ( where mLocationRequest and mLocationCallback are initialized), the OnConnectionFailedListener, the api we want to use i.e the Location Api and build. We call connectGoogleClient() method to connect.

`locationSettingsRequest()` — Method builds the location settings and adds a location request. The method checks location service settings. if location service is not activated, in the OnFailureListener callback, a ResolvableApiException with startResolutionForResult is started which shows a dialog to enable location service. Response from the dialog is handled in onActivityResult. If location service is activated, requestLocationUpdate() is called in the OnSuccessListener callback of the SettingsClient

`saveLocation()` — Method send location updates to the server which is called in mLocationCallback of Fused location client. It takes 3 parameters. The uniqueId for each app instance, longitude and latitude. The location update is sent to the server using the volley library. Response from the server is returned in json and the arrayadapter is updated.

```JAVA
package tafieldscience.realtimefusedlocation;

import android.content.IntentSender;
import android.content.pm.PackageManager;
import android.os.Bundle;
import android.os.Looper;
import android.util.Log;
import android.view.Menu;
import android.view.MenuItem;
import android.widget.ArrayAdapter;
import android.widget.ListView;
import android.widget.Toast;

import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.StringRequest;
import com.android.volley.toolbox.Volley;
import com.google.android.gms.common.ConnectionResult;
import com.google.android.gms.common.GoogleApiAvailability;
import com.google.android.gms.common.api.ApiException;
import com.google.android.gms.common.api.GoogleApiClient;
import com.google.android.gms.common.api.ResolvableApiException;
import com.google.android.gms.location.FusedLocationProviderClient;
import com.google.android.gms.location.LocationCallback;
import com.google.android.gms.location.LocationRequest;
import com.google.android.gms.location.LocationResult;
import com.google.android.gms.location.LocationServices;
import com.google.android.gms.location.LocationSettingsRequest;
import com.google.android.gms.location.LocationSettingsStatusCodes;
import com.google.android.gms.location.SettingsClient;
import com.google.android.gms.tasks.OnCanceledListener;

import org.json.JSONException;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.Calendar;
import java.util.HashMap;
import java.util.Map;
import java.util.UUID;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;

public class RealtimeActivity extends AppCompatActivity {
    private FusedLocationProviderClient mFusedLocationClient;

    private LocationCallback mLocationCallback;
    private LocationRequest mLocationRequest;
    private GoogleApiClient mGoogleApiClient;

    private long interval = 30000;
    private long fastestInterval = 10000;
    private int priority = LocationRequest.PRIORITY_BALANCED_POWER_ACCURACY ;
    private int numberOfUpdates;
    private double Latitude = 0.0, Longitude = 0.0;
    private ArrayAdapter arrayAdapter;
    private ArrayList update = new ArrayList<String>();
    private static final String TAG = "RealtimeActivity";

    // Server url for location updates
    public static String dburl = "http://192.168.42.85/RealtimeLocation/addlocation.php",uniqueId;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_realtime);

        //Creating listview adapter
        arrayAdapter = new ArrayAdapter<String>(this,R.layout.list_layout,update);

        ListView listView = findViewById(R.id.listing_items);
        listView.setAdapter(arrayAdapter);

        //Unique ID for an app instance
        uniqueId = UUID.randomUUID().toString();
    }

    /**
     * Function to connect googleapiclient
     */
    private void connectGoogleClient() {
        GoogleApiAvailability googleAPI = GoogleApiAvailability.getInstance();
        int resultCode = googleAPI.isGooglePlayServicesAvailable(this);
        if (resultCode == ConnectionResult.SUCCESS) {
            mGoogleApiClient.connect();
        } else {
            int REQUEST_GOOGLE_PLAY_SERVICE = 988;
            googleAPI.getErrorDialog(this, resultCode, REQUEST_GOOGLE_PLAY_SERVICE);
        }
    }

    /**
     * Function to start FusedLocation updates
     */
    public void requestLocationUpdate() {
        if (ContextCompat.checkSelfPermission(getApplicationContext(), android.Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED && ContextCompat.checkSelfPermission(getApplicationContext(), android.Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED) {
            mFusedLocationClient.requestLocationUpdates(mLocationRequest, mLocationCallback, Looper.myLooper());
        }
    }

    /**
     * Build GoogleApiClient and connect
     */
    private synchronized void buildGoogleApiClient() {
        mFusedLocationClient = LocationServices.getFusedLocationProviderClient(this);

        mGoogleApiClient = new GoogleApiClient.Builder(this)
                .addConnectionCallbacks(new GoogleApiClient.ConnectionCallbacks() {
                    @Override
                    public void onConnected(@Nullable Bundle bundle) {
                        // Creating a location request
                        mLocationRequest = new LocationRequest();
                        mLocationRequest.setInterval(interval);
                        mLocationRequest.setFastestInterval(fastestInterval);
                        mLocationRequest.setPriority(priority);
                        mLocationRequest.setSmallestDisplacement(0);

                        // FusedLocation callback
                        mLocationCallback = new LocationCallback() {
                            @Override
                            public void onLocationResult(final LocationResult locationResult) {
                                super.onLocationResult(locationResult);

                                Latitude = locationResult.getLastLocation().getLatitude();
                                Longitude = locationResult.getLastLocation().getLongitude();

                                if (Latitude == 0.0 && Longitude == 0.0) {
                                    requestLocationUpdate();
                                } else {
                                    // Check internet permission and
                                    // Send Location to Database
                                    if (ContextCompat.checkSelfPermission(getApplicationContext(), android.Manifest.permission.INTERNET) == PackageManager.PERMISSION_GRANTED) {
                                        saveLocation(uniqueId, Double.toString(Latitude), Double.toString(Longitude));
                                    }
                                }
                            }
                        };

                        // Call location settings function to enable gps
                        locationSettingsRequest();
                    }

                    @Override
                    public void onConnectionSuspended(int i) {
                        connectGoogleClient();
                    }
                })
                .addOnConnectionFailedListener(new GoogleApiClient.OnConnectionFailedListener() {
                    @Override
                    public void onConnectionFailed(@NonNull ConnectionResult connectionResult) {

                    }
                })
                .addApi(LocationServices.API)
                .build();

        // Connect googleapiclient after build
        connectGoogleClient();
    }

    /**
     * Function to request Location Service Dialog
     */
    private void locationSettingsRequest(){
        SettingsClient mSettingsClient = LocationServices.getSettingsClient(this);

        LocationSettingsRequest.Builder builder = new LocationSettingsRequest.Builder();
        builder.addLocationRequest(mLocationRequest);
        builder.setAlwaysShow(true);
        LocationSettingsRequest mLocationSettingsRequest = builder.build();

        mSettingsClient
                .checkLocationSettings(mLocationSettingsRequest)
                .addOnSuccessListener(locationSettingsResponse -> {
                    // Start FusedLocation if GPS is enabled
                    requestLocationUpdate();
                })
                .addOnFailureListener(e -> {
                    // Show enable GPS Dialog and handle dialog buttons
                    int statusCode = ((ApiException) e).getStatusCode();
                    switch (statusCode) {
                        case LocationSettingsStatusCodes.RESOLUTION_REQUIRED:
                            try {
                                int REQUEST_CHECK_SETTINGS = 214;
                                ResolvableApiException rae = (ResolvableApiException) e;
                                rae.startResolutionForResult(RealtimeActivity.this, REQUEST_CHECK_SETTINGS);
                            } catch (IntentSender.SendIntentException sie) {
                                showLog("Unable to Execute Request");
                            }
                            break;
                        case LocationSettingsStatusCodes.SETTINGS_CHANGE_UNAVAILABLE:
                            showLog("Location Settings are Inadequate, and Cannot be fixed here. Fix in Settings");
                    }
                })
                .addOnCanceledListener(new OnCanceledListener() {
                    @Override
                    public void onCanceled() {
                        showLog("Canceled No Thanks");
                    }
                });
    }

    private void showLog(String message) {
        Log.e(TAG, "" + message);
    }

    @Override
    public void onResume(){
        super.onResume();
        buildGoogleApiClient();
    }

    @Override
    public void onDestroy(){
        super.onDestroy();
        try {
            mFusedLocationClient.removeLocationUpdates(mLocationCallback);
            showLog("Canceled Location Thanks");
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        // Inflate the menu; this adds items to the action bar if it is present.
        getMenuInflater().inflate(R.menu.menu_main, menu);
        return true;
    }

    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        // Handle action bar item clicks here. The action bar will
        // automatically handle clicks on the Home/Up button, so long
        // as you specify a parent activity in AndroidManifest.xml.
        int id = item.getItemId();

        if (id == R.id.action_stop) {
            // Stop location updates and disconnect googleapiclient
            // Clear listview
            try {
                mFusedLocationClient.removeLocationUpdates(mLocationCallback);
                mGoogleApiClient.disconnect();
                update.clear();
                arrayAdapter.notifyDataSetChanged();
                Toast.makeText(getApplication(), "Location Stopped.", Toast.LENGTH_LONG).show();
            } catch (Exception e) {
                e.printStackTrace();
            }
            return true;
        }
        return super.onOptionsItemSelected(item);
    }

    /**
     * Function to Save location to db and update listview
     * @param uniqueId unique ID for location in database
     * @param latitude latitude from fused location
     * @param longitude longitude from fused location
     */
    public void saveLocation(String uniqueId, String latitude, String longitude){
        RequestQueue requestQueue = Volley.newRequestQueue(this);
        StringRequest stringRequest = new StringRequest(Request.Method.POST, dburl, new Response.Listener<String>() {
            @Override
            public void onResponse(String response) {
                // Post response from server in JSON
                try {
                    JSONObject jObj = new JSONObject(response);
                    boolean error = jObj.getBoolean("error");
                    if(error) {
                        update.add(Calendar.getInstance().getTime() + " - Location Updated");
                        arrayAdapter.notifyDataSetChanged();
                    }else{
                        update.add(Calendar.getInstance().getTime() + " - Update Failed");
                        arrayAdapter.notifyDataSetChanged();
                    }
                } catch (JSONException e) {
                    e.printStackTrace();
                }
            }
        }, new Response.ErrorListener() {
            @Override
            public void onErrorResponse(VolleyError error) {
                showLog("Volley error: "+error.toString());
            }
        }){
            @Override
            protected Map<String,String> getParams(){
                Map<String,String> params = new HashMap<String, String>();
                // Adding parameters to post request
                params.put("uniqueId",uniqueId);
                params.put("latitude",latitude);
                params.put("longitude",longitude);
                return params;
            }
        };

        // Adding request to request queue
        requestQueue.add(stringRequest);
    }
}
```

### Conclusion

For a beginner it will be always difficult to run this project for the first time. But don’t worry, the following steps will helps you testing this app. (The ip address looks like 192.168.42.85)

⇒ Make sure that both devices (the device running the PHP project and the android device) are on the same wifi network.

⇒ Give correct username , password and database name of MySQL in Config.php

⇒ Replace the URL ip address with your machine ip address. You can get the ip address by running ipconfig in cmd

See source files on github

https://github.com/AnyangweChe/fused-location-provider-android-project
