<?php
require "includes/creds.php";
session_start();
header("Content-Type: text/xml");

echo '<? xml version="1.0" encoding="UTF-8" standalone="no" ?>';

# Output the Gather element
function gather($digits,$action,$audio)
{
  echo "<Gather numDigits='$digits' action='$action'>";
  echo "<Play>$audio</Play>";
  echo "</Gather>";
}

# Output the Play element
function play($audio, $action)
{
  if ( isset($action) ) {
    echo "<Play action='$action'>$audio</Play>";
  } else {
    echo "<Play>$audio</Play>";
  }
}

# Output the Forward element
function forward($location)
{
  echo "<Forward >$location</Forward>";
}

# Get weather from the OpenWeatherMap API
# You'll need to register to get a free key
function getWeather($zip)
{
    global $openweathermapkey;

    $BASE_URL = "https://api.openweathermap.org/data/2.5/weather?zip=$zip,us&appid=$openweathermapkey";
    $session = curl_init($BASE_URL);
    curl_setopt($session, CURLOPT_RETURNTRANSFER,true);
    $json = curl_exec($session);
    $phpObj =  json_decode($json,true);

#    print_r($phpObj);

    if ( isset($phpObj['name']) ) {
        $temp         = round( 9/5*($phpObj['main']['temp']-273.15)+32 );    # F
        $city         = $phpObj['name'];
        $weather_desc = $phpObj['weather'][0]['description'];
        $wind         = round( $phpObj['wind']['speed'] );
        $humidity     = $phpObj['main']['humidity'];

        $speech = "<speak>The current temperature for $city is $temp degrees.<break/>     $weather_desc with humidity at $humidity percent<break/> and wind of $wind miles per hour<break/><break/><break/>Thanks</speak>";
    } else {
        $speech = 0;
    }
    return $speech;
}

# Send text/ssml to AWS which returns a mp3 file.
#  Converts the mp3 file to a wav file which Stratus can play to the caller
#   Requires:
#    /usr/bin/mpg123
#    /usr/bin/sox
function awsSpeech($speech)
{
    global $aws_token;
    global $aws_key;
    
    require 'vendor/autoload.php';
    $s3 = new Aws\Polly\PollyClient([
      'version'     => 'latest',
      'region'      => 'us-west-2',
      'credentials' => [
        'key'    => $aws_token,
        'secret' => $aws_key
      ]
    ]);

    $result = $s3->synthesizeSpeech([
        'LexiconNames' => [],
        'OutputFormat' => 'mp3',
        'SampleRate' => '8000',
        'Text' => $speech,
        'TextType' => 'ssml',
#        'VoiceId' => 'Joanna',
        'VoiceId' => 'Salli',
    ]);

    $tmpName = "polly".uniqid();
    file_put_contents("/tmp/".$tmpName.".mp3",
      $result['AudioStream']->getContents() );
    $cmd1 = '/usr/bin/mpg123 -w '."/tmp/".$tmpName.
      '.wav '."/tmp/".$tmpName.'.mp3';
    $cmd2 = '/usr/bin/sox '."/tmp/".$tmpName.'.wav '.
      ' -e mu-law -r 8000 -c 1 -b 8 '."audio_temp/".$tmpName.".wav";
    $out1 = exec($cmd1);
    $out2 = exec($cmd2);
    return "audio_temp/".$tmpName.".wav";
}

# Main Logic

if (!isset($_REQUEST["case"])) {
  # Nothing received yet.
  gather(5,"index.php?case=playzip","audio_perm/weather_announcement.wav");

}
else if ($_REQUEST["case"] == "playzip") {

  # We should have a zip now
  $speech = getWeather($_REQUEST["Digits"]);

  if ( $speech ) {
    try {
      # Weather data retrieved convert to speech/wav
      $audioPath = awsSpeech($speech);
    } catch ( Exception $e ) {
      # Couldn't convert to speech so play pre-recorded message
      $audioPath = "audio_perm/sorry_no_polly.wav";
    }
  } else {
    # Couldn't get the weather data so play pre-recorded message
    $audioPath = "audio_perm/sorry_cant_find_zip.wav";
  }

  # Play the weather and hang up
  play($audioPath);
}

?>
