<?php

class AuthP12
{
    protected function authWithP12()
    {

        ##
        $key = file_get_contents($args['p12']);

        ##
        $gac = new Google_Auth_AssertionCredentials(
            $args['emailapp'],
            array(
                'https://spreadsheets.google.com/feeds',
                "https://www.googleapis.com/auth/drive",
                "https://www.googleapis.com/auth/drive.file",
                "https://www.googleapis.com/auth/drive.readonly",
                "https://www.googleapis.com/auth/drive.metadata.readonly",
                "https://www.googleapis.com/auth/drive.appdata",
                "https://www.googleapis.com/auth/drive.apps.readonly",
                "https://www.googleapis.com/auth/drive.metadata",
            ),
            $key
        );

        ##
        $this->gc->setAssertionCredentials($gac);

        ##
        if ($this->gc->getAuth()->isAccessTokenExpired()) {
            $this->gc->getAuth()->refreshTokenWithAssertion($gac);
        }

        ##
        $at = json_decode($this->gc->getAuth()->getAccessToken());


        ##
        $serviceRequest = new Google\Spreadsheet\DefaultServiceRequest($at->access_token);
        ##
        Google\Spreadsheet\ServiceRequestFactory::setInstance($serviceRequest);

    }
}