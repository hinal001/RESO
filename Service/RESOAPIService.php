<?php

namespace Services;

class RESOAPIService
{
	private $serviceObj;
	
	public function __construct(RESOAPI $service)
	{
		$this->serviceObj = $service; 
	}

	public function getData()
	{
	}

	public function setData()
	{
	}

	//ADD GENERATE TOKEN
                function generateToken(&$url,$username,$password,$jsonfile)
                {
                        try
                        {
                                $ch = curl_init($url);
                                $fh = fopen($jsonfile,'w') or die($php_errormsg);
                                $content = "grant_type=client_credentials&scope=api&client_id=".$username;
                                                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                                                                curl_setopt($ch, CURLOPT_POST, 1);
                                curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
                                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded;charset=UTF-8','cache-control: no-cache'));
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
                                curl_setopt($ch, CURLOPT_FILE, $fh);
                                $data = curl_exec($ch);
                                                curl_close($ch);
				 return $data;
                        }
                        catch(Exception $e)
                        {
                                return $e->getMessage();
                        }
                }

		function getAccessToken($jsonfile)
                {
                        // Get the contents of the JSON file
                        $strJsonFileContents = file_get_contents($jsonfile);
                        // Convert to array
                        $array = json_decode($strJsonFileContents, true);
                        if(is_array($array))
                        {
                                return $array["access_token"];
                        }
                        else
                               return "";
                        //var_dump($array); // print array
                }

		
		function getDataByCurl($url,$accessToken,$datafile,$postmantoken)
                {
                        try
                        {
                                        echo "SETTING DOWNLOAD: ".$url."\n";
                                        $ch = curl_init($url);
                                        //$fh = fopen($datafile,'w') or die($php_errormsg);
                                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//Receive server response
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                        //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken,'Postman-Token: 82b1e9b1-5ab1-42b1-8fba-ff28bfbf3bcf','cache-control: no-cache'));
                                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken,'Postman-Token: '.$postmantoken,'cache-control: no-cache'));
                                        //curl_setopt($ch, CURLOPT_FILE, $fh);
                                        $data = curl_exec($ch);
					curl_close($ch);
                                       return $data;

                        }
                        catch(Exception $e)
                        {
                                return $e->getMessage();
                        }
                }

		//BULK DATA DOWNLOAD
                function bulkDataDownload($propUrl,$accessToken,$datafile,$postmantoken)
                {
                        try
                        {
                                print "PROP URL IS: ".$propUrl."\n";
                                $jsonArr                =       array();
                                $prop_json              =       $this->getDataByCurl($propUrl,$accessToken,$datafile,$postmantoken);
                                $jsonArr                =       json_decode($prop_json,true);
                                $file                   =       fopen($datafile,"w");

                                fwrite($file,$prop_json);
                                fclose($file);
                                print_r($jsonArr);

                                $nextlink               =       $jsonArr['@odata.nextLink'];
                                print "DATA COUNT IS: ".$jsonArr['@odata.count'];
                                $i=10;
				echo "NEXT LINK IS :".$nextlink."\n";

                                while($nextlink != "")
                                {
                                        print "value of i is: ".$i."\n";
                                        $nextlink               =       $this->query_param_encode($jsonArr['@odata.nextLink']);
                                        print "NEXT LINK BEFORE: ".$nextlink."\n";

                                        $jsonArr                =       array();
                                        $prop_json              =       $this->getDataByCurl($nextlink,$accessToken,$datafile,$postmantoken);
                                        $jsonArr                =       json_decode($prop_json,true);
                                        $file                   =       fopen($datafile,"a");

                                        fwrite($file,$prop_json);
                                        fclose($file);
                                        //print_r($jsonArr);
					if(isset($jsonArr['@odata.nextLink']))
                                        {
                                                $nextlink               =       $jsonArr['@odata.nextLink'];
                                        }
                                        else
                                        {
                                                $nextlink               =       "";
                                        }
                                        echo "NEXT LINK AFTER: ".$nextlink."\n";
                                        $i      =       $i+10;
                                }
                        }
                        catch(Exception $e)
                        {
                                return $e->getMessage();
                        }
                }

		//BULK DATA DOWNLOAD
                function bulkDownload($propUrl,$mediaUrl,$accessToken,$datafile,$mediafile,$postmantoken,$media=false,$offset=false)
                {
                        try
                        {
                                $propArr                                                =               array();
                                                                $listArr                        =       array();
                                print "PROP URL IS: ".$propUrl."\n";
                                $jsonArr                        =       array();
                                $prop_json                      =       $this->getDataByCurl($propUrl,$accessToken,$datafile,$postmantoken);
                                $jsonArr                        =       json_decode($prop_json,true);
                                //offset
                                $cntr                           =       10;

                                $path_parts                     =       pathinfo($datafile);

                                print "OFFSET is :".$offset."\n";
				
                                if($offset==true)
                                {
                                        $datafile               =       $path_parts['dirname']."/".$path_parts['filename']."_".$cntr.".json";
                                        print "FILE IS: ".$datafile."\n";
                                        $file                   =       fopen($datafile,"w");
                                }
                                else
                                {
                                        $file                   =       fopen($datafile,"w");
                                }

                                //print_r($jsonArr['value']);

                                for($i=0;$i<count($jsonArr['value']);$i++)
                                {
                                        $listArr[] = $jsonArr['value'][$i]['ListingId'];
                                        $propArr[] = $jsonArr['value'][$i]['ListingId'];
				                               }

                                //print_r($listArr);

                                if($media==true)
                                {
                                        $fileMedia              =       fopen($mediafile,"w");
                                        $prop_media             =       $this->getMediaByCurl($mediaUrl,$accessToken,$mediafile,$postmantoken,$listArr);
                                        print_r($prop_media);
                                        fwrite($fileMedia,$prop_media);
                                        fclose($fileMedia);
                                }


                                fwrite($file,$prop_json);
                                fclose($file);
                                //print_r($jsonArr);

                                $nextlink               =       $jsonArr['@odata.nextLink'];
                                print "DATA COUNT IS: ".$jsonArr['@odata.count'];
				
                                echo "NEXT LINK IS :".$nextlink."\n";

                                while($nextlink != "")
                                {
                                        $cntr   =       $cntr+10;
                                        print "value of CNTR is: ".$cntr."\n";
                                        $nextlink               =       $this->query_param_encode($jsonArr['@odata.nextLink']);
                                        print "NEXT LINK BEFORE: ".$nextlink."\n";

                                        $jsonArr                =       array();
                                        $prop_json              =       $this->getDataByCurl($nextlink,$accessToken,$datafile,$postmantoken);
                                        $jsonArr                =       json_decode($prop_json,true);

                                        $listArr                =       array();
					
                                        for($i=0;$i<count($jsonArr['value']);$i++)
                                        {
                                                $listArr[] =    $jsonArr['value'][$i]['ListingId'];
                                                $propArr[] =    $jsonArr['value'][$i]['ListingId'];
                                        }

                                        print_r($listArr);

                                        if($offset==true)
                                        {
                                                $datafile               =       $path_parts['dirname']."/".$path_parts['filename']."_".$cntr.".json";
                                                print "DATA FILE IS: ".$datafile."\n";
                                                $file                   =       fopen($datafile,"w");
                                        }
					else
                                        {
                                                //$file                 =       fopen($datafile,"w");
                                                $file                   =       fopen($datafile,"a");
                                        }

                                        //WRITE IN FILE
                                        fwrite($file,$prop_json);
                                        fclose($file);

                                        if($media==true)
                                        {

                                                $prop_media             =       $this->getMediaByCurl($mediaUrl,$accessToken,$mediafile,$postmantoken,$listArr);
                                                $fileMedia              =       fopen($mediafile,"a");
                                                fwrite($fileMedia,$prop_media);
                                                fclose($fileMedia);
					 }

                                        //print_r($jsonArr);

                                        if(isset($jsonArr['@odata.nextLink']))
                                        {
                                                $nextlink               =       $jsonArr['@odata.nextLink'];
                                        }
                                        else
                                        {
                                                $nextlink               =       "";
                                        }
                                        echo "NEXT LINK AFTER: ".$nextlink."\n";

                                }
                        }
                        catch(Exception $e)
			{
                                return $e->getMessage();
                        }
                                                return $propArr;
                }

                                //DOWNLOAD ROOM INFORMATION
                                function bulkDownloadRoom($roomUrl,$accessToken,$roomfile,$postmantoken,$dataArr)
                {
                        try
                        {
                                                        $fileRoom       =       fopen($roomfile,"w");
                                                        $cntr       =       10;
                                                        foreach($dataArr as $key => $value)
                                                        {
                                                                if(trim($value)!="")
                                                                {
	$ListOfficeKey="";

   $RoomDescription="";

   $RoomLevel="";

   $RoomLengthWidthSource="";

   $RoomAreaSource="";

   $RoomFlooring ="";

   $RoomFeatures="";
                                                                                $RoomLength ="";
                                                                                $OriginatingSystemName="";
                                                                                $RoomKey  ="";

   $RoomType="";
$RoomLengthWidthUnits  ="";

   $RoomArea   ="";

   $RoomWidth  ="";

   $ListingId="";

   $ModificationTimestamp="";

   $ListingKey="";

   $ListingKeyNumeric="";

   $RoomDimensions ="";

   $RoomAreaUnits ="";

   $RoomKeyNumeric="";

   $OriginatingSystemSubName="";

	
   print $value."\n";

   $queryFilter    = "";

   $queryFilter    =       "(ListingId eq '".trim($value)."')";

   $roomGetURL             =  "";

   $roomGetURL             =   $roomUrl;

   $roomGetURL             =       $this->addGetParamToUrl2($roomGetURL,'$filter',$this->urlSpaceRemove($queryFilter));
                                                                                $prop_json      =   $this->getDataByCurl($roomGetURL,$accessToken,$roomfile,$postmantoken);

   $jsonArr                =       json_decode($prop_json,true);

                                                                                for($k=0;$k<count($jsonArr['value']);$k++)
                                                                              {


           $ListOfficeKey                          =       $jsonArr['value'][$k]['ListOfficeKey'];
                                                                                        $RoomDescription                        =       $jsonArr['value'][$k]['RoomDescription'];

           $RoomLevel                                      =       $jsonArr['value'][$k]['RoomLevel'];
                                                                                        $RoomLengthWidthSource          =       $jsonArr['value'][$k]['RoomLengthWidthSource'];
                                                                                        $RoomAreaSource                         =       $jsonArr['value'][$k]['RoomAreaSource'];
                                                                                        $RoomFlooring                           =       $jsonArr['value'][$k]['RoomFlooring'];

           $RoomFeatures                           =       $jsonArr['value'][$k]['RoomFeatures'];

	           $RoomLength                                     =       $jsonArr['value'][$k]['RoomLength'];

           $OriginatingSystemName          =       $jsonArr['value'][$k]['OriginatingSystemName'];

           $RoomKey                                        =       $jsonArr['value'][$k]['RoomKey'];

           $RoomType                                       =       $jsonArr['value'][$k]['RoomType'];

           $RoomLengthWidthUnits           =       $jsonArr['value'][$k]['RoomLengthWidthUnits'];

           $RoomArea                                       =       $jsonArr['value'][$k]['RoomArea'];

           $RoomWidth                                      =       $jsonArr['value'][$k]['RoomWidth'];

	           $ListingId                                      =       $jsonArr['value'][$k]['ListingId'];

           $ModificationTimestamp          =       $jsonArr['value'][$k]['ModificationTimestamp'];

           $ListingKey                                     =       $jsonArr['value'][$k]['ListingKey'];

           $ListingKeyNumeric                      =       $jsonArr['value'][$k]['ListingKeyNumeric'];

           $RoomDimensions                         =       $jsonArr['value'][$k]['RoomDimensions'];

           $RoomAreaUnits                          =       $jsonArr['value'][$k]['RoomAreaUnits'];

           $RoomKeyNumeric                         =       $jsonArr['value'][$k]['RoomKeyNumeric'];

		
           $OriginatingSystemSubName       =       $jsonArr['value'][$k]['OriginatingSystemSubName'];


                                                                                        $urlString = "";

           $urlString.=$ListOfficeKey;

           $urlString.="|";
                                                                                        $urlString.=$RoomLevel;

           $urlString.="|";

           $urlString.=$RoomLengthWidthSource;
                                                                                        $urlString.="|";

           $urlString.=$RoomAreaSource;
	
	          $urlString.="|";
                                                                                        $urlString.=$RoomFlooring;

           $urlString.="|";

           $urlString.=$RoomFeatures;

           $urlString.="|";

           $urlString.=$RoomLength;

           $urlString.="|";

           $urlString.=$OriginatingSystemName;

           $urlString.="|";

           $urlString.=$RoomKey;

	            $urlString.="|";

           $urlString.=$RoomType;

           $urlString.="|";

           $urlString.=$RoomLengthWidthUnits;

           $urlString.="|";

           $urlString.=$RoomArea;

           $urlString.="|";

           $urlString.=$RoomWidth;

           $urlString.="|";

           $urlString.=$ListingId;

           $urlString.="|";

	  $urlString.=$ModificationTimestamp;

           $urlString.="|";

           $urlString.=$ListingKey;

           $urlString.="|";

           $urlString.=$ListingKeyNumeric;

           $urlString.="|";

           $urlString.=$RoomDimensions;

           $urlString.="|";

           $urlString.=$RoomAreaUnits;

           $urlString.="|";

           $urlString.=$RoomKeyNumeric;

           $urlString.="|";	
			
	         $urlString.=$OriginatingSystemSubName;

           $urlString.="|";

           $urlString.=$RoomDescription;

           $urlString.="|";

           $urlString.="\n";

           fwrite($fileRoom,$urlString);

   }

                                                                                if(isset($jsonArr['@odata.nextLink']) && $jsonArr['@odata.nextLink']!="")
                                                                                {
	
           $nextlink                        =      $jsonArr['@odata.nextLink'];

           print "NEXT LINK IS :".$nextlink."\n";


           while($nextlink != "")                                             
                                                                                        {
                                                                                                $cntr   =       $cntr+10;
                                                                                                print "COUNTER: ".$cntr."\n";
                                                                                                $nextlink               =       $this->query_param_encode($jsonArr['@odata.nextLink']);

		
                   print "NEXT LINK BEFORE: ".$nextlink."\n";


                   $jsonArr                =       array();

                   $prop_json              =       $this->getDataByCurl($nextlink,$accessToken,$roomfile,$postmantoken);
                                                                                                $jsonArr                =       json_decode($prop_json,true);


                   for($k=0;$k<count($jsonArr['value']);$k++)
                                                                                                {


                           $ListOfficeKey                          =       $jsonArr['value'][$k]['ListOfficeKey'];

			
                           $RoomDescription                        =       $jsonArr['value'][$k]['RoomDescription'];
                                                                                                        $RoomLevel                                      =       $jsonArr['value'][$k]['RoomLevel'];

                           $RoomLengthWidthSource          =       $jsonArr['value'][$k]['RoomLengthWidthSource'];

                           $RoomAreaSource                         =       $jsonArr['value'][$k]['RoomAreaSource'];

                           $RoomFlooring                           =       $jsonArr['value'][$k]['RoomFlooring'];

                           $RoomFeatures                           =       $jsonArr['value'][$k]['RoomFeatures'];
				
			                                                                                                      $RoomLength                                     =       $jsonArr['value'][$k]['RoomLength'];

                           $OriginatingSystemName          =       $jsonArr['value'][$k]['OriginatingSystemName'];

                           $RoomKey                                        =       $jsonArr['value'][$k]['RoomKey'];

                           $RoomType                                       =       $jsonArr['value'][$k]['RoomType'];

                           $RoomLengthWidthUnits           =       $jsonArr['value'][$k]['RoomLengthWidthUnits'];

                           $RoomArea                                       =       $jsonArr['value'][$k]['RoomArea'];

                           $RoomWidth                                      =       $jsonArr['value'][$k]['RoomWidth'];

			 
                           $ListingId                                      =       $jsonArr['value'][$k]['ListingId'];

                           $ModificationTimestamp          =       $jsonArr['value'][$k]['ModificationTimestamp'];

                           $ListingKey                                     =       $jsonArr['value'][$k]['ListingKey'];

                           $ListingKeyNumeric                      =       $jsonArr['value'][$k]['ListingKeyNumeric'];

                           $RoomDimensions                         =       $jsonArr['value'][$k]['RoomDimensions'];

                           $RoomAreaUnits                          =       $jsonArr['value'][$k]['RoomAreaUnits'];

                           $RoomKeyNumeric                         =       $jsonArr['value'][$k]['RoomKeyNumeric'];

                           $OriginatingSystemSubName       =       $jsonArr['value'][$k]['OriginatingSystemSubName'];

			                                                                                                       $urlString = "";

                           $urlString.=$ListOfficeKey;

                           $urlString.="|";
                                                                                                        $urlString.=$RoomLevel;

                           $urlString.="|";

                           $urlString.=$RoomLengthWidthSource;
                                                                                                        $urlString.="|";

                           $urlString.=$RoomAreaSource;

                           $urlString.="|";
                                                                                                        $urlString.=$RoomFlooring;

                           $urlString.="|";

                           $urlString.=$RoomFeatures;

				 $urlString.="|";

                           $urlString.=$RoomLength;

                           $urlString.="|";

                           $urlString.=$OriginatingSystemName;

                           $urlString.="|";

                           $urlString.=$RoomKey;

                           $urlString.="|";

                           $urlString.=$RoomType;

                           $urlString.="|";

                           $urlString.=$RoomLengthWidthUnits;

                           $urlString.="|";

			  
                           $urlString.="|";

                           $urlString.=$RoomArea;

                           $urlString.="|";

                           $urlString.=$RoomWidth;

                           $urlString.="|";

                           $urlString.=$ListingId;

                           $urlString.="|";

                           $urlString.=$ModificationTimestamp;

                           $urlString.="|";

                           $urlString.=$ListingKey;

                           $urlString.="|";

			$urlString.=$ListingKeyNumeric;

                           $urlString.="|";

                           $urlString.=$RoomDimensions;

                           $urlString.="|";

                           $urlString.=$RoomAreaUnits;

                           $urlString.="|";

                           $urlString.=$RoomKeyNumeric;

                           $urlString.="|";

                           $urlString.=$OriginatingSystemSubName;

                           $urlString.="|";

                           $urlString.=$RoomDescription;

			                          $urlString.="|";

                           $urlString.="\n";

                           fwrite($fileRoom,$urlString);

                   }

                                                                                                //fwrite($fileMedia,$prop_json);


                   if(isset($jsonArr['@odata.nextLink']))

                   {

                           $nextlink               =       $jsonArr['@odata.nextLink'];
                                                                                                }
                                                                                                else
                                                                                                {
                                                                                                        $nextlink               =       "";
                                                                                                }
                                                                                                print "NEXT LINK AFTER: ".$nextlink."\n";                  

           }
}
                                                                }
                                                        }
                                                        fclose($fileRoom);
                                                }
                                                catch(Exception $e)
                                                {
                                                        return $e->getMessage();
                                                }                             

                }

                                //DOWNLOAD UNIT INFORMATION
                                function bulkDownloadUnit($unitUrl,$accessToken,$unitfile,$postmantoken,$dataArr)
                {

			                        try
                        {
                                                        $fileunit       =       fopen($unitfile,"w");
                                                        $cntr       =       10;
                                                        foreach($dataArr as $key => $value)
                                                        {                     
                                                                if(trim($value)!="")
                                                                {
                                                                        $ListOfficeKey="";
                                                                        $UnitTypeProForma="";
                                                                        $ListingKeyNumeric="";
                                                                        $UnitTypeTotalRent="";
                                                                        $UnitTypeUnitsTotal="";
									 $OriginatingSystemName="";
                                                                        $UnitTypeKeyNumeric="";
                                                                        $UnitTypeGarageAttachedYN="";
                                                                        $UnitTypeGarageSpaces="";
                                                                        $UnitTypeBathsTotal="";
                                                                        $UnitTypeFurnished="";
                                                                        $UnitTypeActualRent="";
                                                                        $UnitTypeType="";
                                                                        $UnitTypeKey="";
                                                                        $ListingId="";
                                                                        $ModificationTimestamp="";
									$OriginatingSystemSubName="";
                                                                        $UnitTypeBedsTotal="";
                                                                        $ListingKey="";
                                                                        $UnitTypeDescription="";

                                                                        print $value."\n";
                                                                        $queryFilter    = "";
                                                                        $queryFilter    =       "(ListingId eq '".trim($value)."')";
                                                                        $unitGetURL             =  "";
                                                                        $unitGetURL             =   $unitUrl;

									$unitGetURL             =       $this->addGetParamToUrl2($unitGetURL,'$filter',$this->urlSpaceRemove($queryFilter));
                                                                        $prop_json      =   $this->getDataByCurl($unitGetURL,$accessToken,$unitfile,$postmantoken);
                                                                        $jsonArr                =       json_decode($prop_json,true);
                                                                        //print "UNIT DATA IS \n";
                                                                        //print_r($jsonArr);

                                                                        for($k=0;$k<count($jsonArr['value']);$k++)
                                                                        {



   $ListOfficeKey                                          =       $jsonArr['value'][$k]['ListOfficeKey'];
                                                                                $UnitTypeProForma                                       =       $jsonArr['value'][$k]['UnitTypeProForma'];

$ListingKeyNumeric                                      =       $jsonArr['value'][$k]['ListingKeyNumeric'];

   $UnitTypeTotalRent                                      =       $jsonArr['value'][$k]['UnitTypeTotalRent'];

   $UnitTypeUnitsTotal                                     =       $jsonArr['value'][$k]['UnitTypeUnitsTotal'];
                                                                                $OriginatingSystemName                          =       $jsonArr['value'][$k]['OriginatingSystemName'];

   $UnitTypeKeyNumeric                                     =       $jsonArr['value'][$k]['UnitTypeKeyNumeric'];

   $UnitTypeGarageAttachedYN                       =       $jsonArr['value'][$k]['UnitTypeGarageAttachedYN'];

   $UnitTypeGarageSpaces                           =       $jsonArr['value'][$k]['UnitTypeGarageSpaces'];

	$UnitTypeBathsTotal                                     =       $jsonArr['value'][$k]['UnitTypeBathsTotal'];

   $UnitTypeFurnished                                      =       $jsonArr['value'][$k]['UnitTypeFurnished'];

   $UnitTypeActualRent                                     =       $jsonArr['value'][$k]['UnitTypeActualRent'];

   $UnitTypeType                                           =       $jsonArr['value'][$k]['UnitTypeType'];

   $UnitTypeKey                                            =       $jsonArr['value'][$k]['UnitTypeKey'];

   $ListingId                                                      =       $jsonArr['value'][$k]['ListingId'];

   $ModificationTimestamp                          =       $jsonArr['value'][$k]['ModificationTimestamp'];


   $OriginatingSystemSubName                       =       $jsonArr['value'][$k]['OriginatingSystemSubName'];

   $UnitTypeBedsTotal                                      =       $jsonArr['value'][$k]['UnitTypeBedsTotal'];

   $ListingKey                                                     =       $jsonArr['value'][$k]['ListingKey'];

   $UnitTypeDescription                            =       $jsonArr['value'][$k]['UnitTypeDescription'];


                                                                                $urlString = "";

   $urlString.=$ListOfficeKey;

   $urlString.="|";

   $urlString.=$UnitTypeProForma;

   $urlString.="|";

   $urlString.=$ListingKeyNumeric;
                                                                                $urlString.="|";

   $urlString.=$UnitTypeTotalRent;

   $urlString.="|";
                                                                                $urlString.=$UnitTypeUnitsTotal;

   $urlString.="|";

   $urlString.=$OriginatingSystemName;

   $urlString.="|";

	$urlString.=$UnitTypeKeyNumeric;

   $urlString.="|";

   $urlString.=$UnitTypeGarageAttachedYN;

   $urlString.="|";

   $urlString.=$UnitTypeGarageSpaces;

   $urlString.="|";

   $urlString.=$UnitTypeBathsTotal;

   $urlString.="|";

   $urlString.=$UnitTypeFurnished;

   $urlString.="|";

	$urlString.=$UnitTypeActualRent;

   $urlString.="|";

   $urlString.=$UnitTypeType;

   $urlString.="|";

   $urlString.=$UnitTypeKey;

   $urlString.="|";

   $urlString.=$ListingId;

   $urlString.="|";

   $urlString.=$ModificationTimestamp;

   $urlString.="|";

	$urlString.=$OriginatingSystemSubName;

   $urlString.="|";

   $urlString.=$UnitTypeBedsTotal;

   $urlString.="|";

   $urlString.=$ListingKey;

   $urlString.="|";

   $urlString.=$UnitTypeDescription;

   $urlString.="|";

   $urlString.="\n";

   fwrite($fileunit,$urlString);

 }

                                                                        if(isset($jsonArr['@odata.nextLink']) && $jsonArr['@odata.nextLink']!="")
                                                                        {


   $nextlink                        =      $jsonArr['@odata.nextLink'];

   print "NEXT LINK IS :".$nextlink."\n";

                                                                                while($nextlink != "")                                                     

   {

           $cntr   =       $cntr+10;

           print "COUNTER: ".$cntr."\n";

           $nextlink               =       $this->query_param_encode($jsonArr['@odata.nextLink']);

		print "NEXT LINK BEFORE: ".$nextlink."\n";


           $jsonArr                =       array();

           $prop_json              =       $this->getDataByCurl($nextlink,$accessToken,$unitfile,$postmantoken);
                                                                                        $jsonArr                =       json_decode($prop_json,true);


           for($k=0;$k<count($jsonArr['value']);$k++)

           {

                                                                                                $ListOfficeKey                                          =       $jsonArr['value'][$k]['ListOfficeKey'];

                   $UnitTypeProForma                                       =       $jsonArr['value'][$k]['UnitTypeProForma'];
		
		 $ListingKeyNumeric                                      =       $jsonArr['value'][$k]['ListingKeyNumeric'];
                                                                                                $UnitTypeTotalRent                                      =       $jsonArr['value'][$k]['UnitTypeTotalRent'];
                                                                                                $UnitTypeUnitsTotal                                     =       $jsonArr['value'][$k]['UnitTypeUnitsTotal'];
                                                                                                $OriginatingSystemName                          =       $jsonArr['value'][$k]['OriginatingSystemName'];

                   $UnitTypeKeyNumeric                                     =       $jsonArr['value'][$k]['UnitTypeKeyNumeric'];

                   $UnitTypeGarageAttachedYN                       =       $jsonArr['value'][$k]['UnitTypeGarageAttachedYN'];

                   $UnitTypeGarageSpaces                           =       $jsonArr['value'][$k]['UnitTypeGarageSpaces'];

                   $UnitTypeBathsTotal                                     =       $jsonArr['value'][$k]['UnitTypeBathsTotal'];
	
		 $UnitTypeFurnished                                      =       $jsonArr['value'][$k]['UnitTypeFurnished'];

                   $UnitTypeActualRent                                     =       $jsonArr['value'][$k]['UnitTypeActualRent'];

                   $UnitTypeType                                           =       $jsonArr['value'][$k]['UnitTypeType'];

                   $UnitTypeKey                                            =       $jsonArr['value'][$k]['UnitTypeKey'];

                   $ListingId                                                      =       $jsonArr['value'][$k]['ListingId'];

                   $ModificationTimestamp                          =       $jsonArr['value'][$k]['ModificationTimestamp'];

		$OriginatingSystemSubName                       =       $jsonArr['value'][$k]['OriginatingSystemSubName'];

                   $UnitTypeBedsTotal                                      =       $jsonArr['value'][$k]['UnitTypeBedsTotal'];

                   $ListingKey                                                     =       $jsonArr['value'][$k]['ListingKey'];

                   $UnitTypeDescription                            =       $jsonArr['value'][$k]['UnitTypeDescription'];


                                                                                                $urlString = "";

                   $urlString.=$ListOfficeKey;
		
		
                   $urlString.="|";
                                                                                                $urlString.=$UnitTypeProForma;

                   $urlString.="|";

                   $urlString.=$ListingKeyNumeric;
                                                                                                $urlString.="|";

                   $urlString.=$UnitTypeTotalRent;

                   $urlString.="|";
                                                                                                $urlString.=$UnitTypeUnitsTotal;

                   $urlString.="|";

                   $urlString.=$OriginatingSystemName;

                   $urlString.="|";

		$urlString.=$UnitTypeKeyNumeric;

                   $urlString.="|";

                   $urlString.=$UnitTypeGarageAttachedYN;

                   $urlString.="|";

                   $urlString.=$UnitTypeGarageSpaces;

                   $urlString.="|";

                   $urlString.=$UnitTypeBathsTotal;

                   $urlString.="|";

                   $urlString.=$UnitTypeFurnished;

                   $urlString.="|";

		$urlString.=$UnitTypeActualRent;

                   $urlString.="|";

                   $urlString.=$UnitTypeType;

                   $urlString.="|";

                   $urlString.=$UnitTypeKey;

                   $urlString.="|";

                   $urlString.=$ListingId;

                   $urlString.="|";

                   $urlString.=$ModificationTimestamp;

                   $urlString.="|";

		$urlString.=$OriginatingSystemSubName;

                   $urlString.="|";

                   $urlString.=$UnitTypeBedsTotal;

                   $urlString.="|";

                   $urlString.=$ListingKey;

                   $urlString.="|";

                   $urlString.=$UnitTypeDescription;

                   $urlString.="|";

                   $urlString.="\n";

                   fwrite($fileunit,$urlString);

           }

	if(isset($jsonArr['@odata.nextLink']))

           {

                   $nextlink               =       $jsonArr['@odata.nextLink'];
                                                                                        }
                                                                                        else
                                                                                        {
                                                                                                $nextlink               =       "";
                                                                                        }
                                                                                        print "NEXT LINK AFTER: ".$nextlink."\n";                          

   }
                                                                        }
                                                                }

							}
                                                        fclose($fileunit);
                                                }
                                                catch(Exception $e)
                                                {
                                                        return $e->getMessage();
                                                }                             

                }


		 function getROOMLIST($roomUrl,$accessToken,$postmantoken)
                                {
                                        try
                                        {
                                                        $propArr                =               array();
                                                        $listArr        =       array();
                                                        $jsonArr        =       array();
                                                        $prop_json      =       $this->getDataByCurl($roomUrl,$accessToken,"",$postmantoken);
                                                        $jsonArr        =       json_decode($prop_json,true);
                                                        $cntr           =       10;

                                                        for($i=0;$i<count($jsonArr['value']);$i++)
                                                        {
                                                                        $listArr[] = $jsonArr['value'][$i]['ListingId'];
									$propArr[] = $jsonArr['value'][$i]['ListingId'];
                                                        }


                                                        if(isset($jsonArr['@odata.nextLink']))
                                                        {
                                                                $nextlink               =       $jsonArr['@odata.nextLink'];
                                                                echo "NEXT LINK IS :".$nextlink."\n";
                                                        }
                                                        else
                                                        {
                                                                $nextlink               =       "";
                                                        }
							

                                                        while($nextlink != "")
                                                        {
                                                                $cntr   =       $cntr+10;
                                                                print "value of CNTR is: ".$cntr."\n";
                                                                $nextlink               =       $this->query_param_encode($jsonArr['@odata.nextLink']);
                                                                print "NEXT LINK BEFORE: ".$nextlink."\n";

                                                                $jsonArr                =       array();
                                                                $prop_json              =       $this->getDataByCurl($nextlink,$accessToken,"",$postmantoken);
                                                                $jsonArr                =       json_decode($prop_json,true);
                                                                //$listArr                =       array();

								for($i=0;$i<count($jsonArr['value']);$i++)
                                                                {
                                                                                $listArr[] =    $jsonArr['value'][$i]['ListingId'];

   $propArr[] =    $jsonArr['value'][$i]['ListingId'];
                                                                }

                                                                if(isset($jsonArr['@odata.nextLink']))
                                                                {
                                                                        $nextlink               =       $jsonArr['@odata.nextLink'];
                                                                }
                                                                else
                                                                {
                                                                        $nextlink               =       "";
                                                                }
                                                                echo "NEXT LINK AFTER: ".$nextlink."\n";
                                                        }

						}
                                        catch(Exception $e)
                                        {
                                                        return $e->getMessage();
                                        }
                                        return $listArr;
                                }


                                function getUNITLIST($unitUrl,$accessToken,$postmantoken)
                                {
                                        try
                                        {
                                                        $propArr                =               array();
                                                        $listArr        =       array();

							$jsonArr        =       array();
                                                        $prop_json      =       $this->getDataByCurl($unitUrl,$accessToken,"",$postmantoken);
                                                        $jsonArr        =       json_decode($prop_json,true);
                                                        $cntr           =       10;

                                                        for($i=0;$i<count($jsonArr['value']);$i++)
                                                        {
                                                                        $listArr[] = $jsonArr['value'][$i]['ListingId'];
                                                                        $propArr[] = $jsonArr['value'][$i]['ListingId'];
                                                        }

                                                        if(isset($jsonArr['@odata.nextLink']))
                                                        {

								$nextlink               =       $jsonArr['@odata.nextLink'];
                                                                echo "NEXT LINK IS :".$nextlink."\n";
                                                        }
                                                        else
                                                        {
                                                                $nextlink               =       "";
                                                        }

                                                        while($nextlink != "")
                                                        {
                                                                $cntr   =       $cntr+10;
                                                                print "value of CNTR is: ".$cntr."\n";
                                                                $nextlink               =       $this->query_param_encode($jsonArr['@odata.nextLink']);

								print "NEXT LINK BEFORE: ".$nextlink."\n";

                                                                $jsonArr                =       array();
                                                                $prop_json              =       $this->getDataByCurl($nextlink,$accessToken,"",$postmantoken);
                                                                $jsonArr                =       json_decode($prop_json,true);
                                                                //$listArr              =       array();

                                                                for($i=0;$i<count($jsonArr['value']);$i++)
                                                                {
                                                                                $listArr[] =    $jsonArr['value'][$i]['ListingId'];

$propArr[] =    $jsonArr['value'][$i]['ListingId'];
                                                                }

                                                                if(isset($jsonArr['@odata.nextLink']))
                                                                {
                                                                        $nextlink               =       $jsonArr['@odata.nextLink'];
                                                                }
                                                                else
                                                                {
                                                                        $nextlink               =       "";
                                                                }
                                                                echo "NEXT LINK AFTER: ".$nextlink."\n";
                                                        }
                                        }
                                        catch(Exception $e)
                                        {

						 return $e->getMessage();
                                        }
                                        return $listArr;
                                }


			//DOWNLOAD MEDIA WITH LISTINGKEY
                function getMediaByCurl($mediaUrl,$accessToken,$mediafile,$postmantoken,$listArr)
                {
                        try
                        {
                                                        $queryStr = "";
                                                        $queryStr = $this->genqueryString('ResourceRecordKey',$listArr);
                                                        print "QUERY STRING IS: ".$queryStr."\n";
                                                        $mediaUrl               =       $this->addGetParamToUrl($mediaUrl,'$filter',$queryStr);
                                                        $mediaUrl               =       $this->addGetParamToUrl($mediaUrl,'$select','MediaURL,Order,ResourceRecordKey');
                                                        $mediaUrl               =       $this->addGetParamToUrl($mediaUrl,'$orderby','ResourceRecordKey');
                                                        print "SETTING DOWNLOAD: ".$mediaUrl."\n";
                                                        $ch = curl_init($mediaUrl);
                                                        //$fh = fopen($datafile,'w') or die($php_errormsg);
							 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//Receive server response
                                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                                        //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken,'Postman-Token: 82b1e9b1-5ab1-42b1-8fba-ff28bfbf3bcf','cache-control: no-cache'));
                                                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken,'Postman-Token: '.$postmantoken,'cache-control: no-cache'));
                                                        //curl_setopt($ch, CURLOPT_FILE, $fh);
                                                        $data = curl_exec($ch);
                                                        curl_close($ch);
                                                        return $data;
							 }
                        catch(Exception $e)
                        {
                                return $e->getMessage();
                        }
                }

                //GENERATE QUERY STRING
                function genqueryString($key,$keyArr)
                {
                        $keys = array_keys($keyArr);
                        $last_key = array_pop($keys);

                        $qryStr         =  "";
                        $qryStr.="(";
                        for($j=0;$j<count($keyArr);$j++)
                        {
                                $qryStr.="$key eq '".$keyArr[$j]."'";
				if($j != $last_key)
                                {
                                        $qryStr.=" or ";
                                }
                        }
                        $qryStr.=")";
                        return $this->urlSpaceRemove(trim($qryStr));
                        //(ListingKey eq 'James' or toupper(MemberFirstName) eq 'ADAM')

                }


		function getDataSystem($resoMgr,$url,$accessToken,$datafile)
                {
                        try
                        {
                                        echo "SETTING DOWNLOAD: ".$url."\n";
                                        $ch = curl_init($url);
                                        $fh = fopen($datafile,'w') or die($php_errormsg);
                                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//Receive server response
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken,'Postman-Token: 82b1e9b1-5ab1-42b1-8fba-ff28bfbf3bcf','cache-control: no-cache'));
                                        curl_setopt($ch, CURLOPT_FILE, $fh);
                                        $data = curl_exec($ch);
                                        curl_close($ch);
                                        return $data;

                        }
			catch(Exception $e)
                        {
                                return $e->getMessage();
                        }
                }


               function getTimeInterval($interVal,$format)
               {
                                        try
                                        {
                                                        if (ctype_digit($interVal))
                                                        {
                                                                        switch ($format)
                                                                        {
                                                                                                        case "Y":
			 // CHECK FOR DIGITS ONLY AND 4DIGITS.

                                           $date = exec("date -d '".$interVal." year ago' +'%Y-%m-%dT%H:%M:%S'");

                                           break;

                           case "M":

                                           $date = exec("date -d '".$interVal." month ago' +'%Y-%m-%dT%H:%M:%S'");

                                           break;

                           case "D":
                                                                                                                        $date = exec("date -d '".$interVal." day ago' +'%Y-%m-%dT%H:%M:%S'");
                                                                                                                        break;

                           case "F":
					
                                           $date = exec("date -d '".$interVal." day ago' +'%Y-%m-%d%H%M%S'");

                                           break;
                                                                                                        case "HR":
                                                                                                                        $date = exec("date -d '".$interVal." hour ago' +'%Y-%m-%dT%H:%M:%S.0000000Z'");

                                           break;
                                                                                                        case "HRM":
                                                                                                                        $date = exec("date -d '".$interVal." minutes ago' +'%Y-%m-%dT%H:%M:%S.0000000Z'");

                                           break;
			 case "MNT":
                                                                                                                        $date = exec("date -d '".$interVal." minutes ago' +'%Y-%m-%dT%H:%M:%S.0000000Z'");

                                           break;
                                                                                                        case "ST":
                                                                                                                        $date = exec("date +'%Y-%m-%d'");;
                                                                                                                        break;

                           case "FLD":

                                           $date = exec("date +'%Y-%m-%d_%H%M%S'");;
                                                                                                                        break;

                           default:
					
                                           $date = exec("date +'%Y-%m-%dT%H:%M:%S'");
                                                                                                                        break;
                                                                        }
                                                        }
                                                        else
                                                        {
                                                                        print "INTERVAL is :".$interVal."\n";
                                                                        exit;
                                                        }
                                                        return $date;
                                        }
                                        catch(Exception $e)
                                        {
                                                                        return $e->getMessage();
                                        }
                }


		function query_param_encode($url)
                {
                        $url = parse_url($url);
                        $url_str = "";
                        if (isset($url['scheme']))
                                $url_str .= $url['scheme'].'://';
                        if (isset($url['host']))
                                $url_str .= $url['host'];
                        if (isset($url['path']))
                                $url_str .= $url['path'];
                        if (isset($url['query']))
                        {
                                //$query = explode('&', $url['query']);
//                              foreach ($query as $j=>$value)
//                              {
//                                      $value = explode('=', $value, 2);
//                                      if (count($value) == 2)
//                                              $query[$j] = rawurlencode($value[0]).'='.rawurlencode($value[1]);
//                                      else
//                                              $query[$j] = rawurlencode($value[0]);
//                              }
				$url_str .= '?'.implode('&', $query);
                                $url_str .= '?'.str_replace(" ","%20",$url['query']);
                        }
                        return $url_str;
                }


                function urlSpaceRemove($url)
                {
                        $url    =       str_replace(" ","%20",$url);
                        return $url;
                }


                //GET FILE TIMESTAMP FROM THE TIMESTAMP FILE
                function getFileTimestamp($fileName)
                {
                        try
                        {

				 $tstamp          = $this->getTimeInterval('10',"MNT");
                                if(file_exists($fileName))
                                {
                                        $content = file($fileName);
                                        $timestamp = trim($content[0]);
                                }
                                else
                                {
                                        echo "Timestamp file ".$fileName." does not exist has been created </br>\n";
                                        $ourFH = fopen($fileName, 'w') or die("can't open file");
                                        fwrite($ourFH, $tstamp);
                                        fclose($ourFH);
                                       $timestamp = $tstamp;
                                }
                                return $timestamp;
                        }
                        catch(Exception $e)
                        {
                                        return $e->getMessage();
                        }
			  }

                //PUT FILE TIMESTAMP
                function putFileTimestamp($fileName)
                {
                        try
                        {
                                    $tstamp =        $this->getTimeInterval('320',"HRM");
                                print "WRITING TIME TO FILE: ".$tstamp."\n";
                                if(file_exists($fileName))
                                {
                                        $ourFH = fopen($fileName, 'w') or die("can't open file");
                                        fwrite($ourFH, $tstamp);
                                        fclose($ourFH);
                                        $timestamp = $tstamp;
                                }
                                else
                                {
                                        print "File does not exist \n";
                                }
				
 return $tstamp;
                        }
                        catch(Exception $e)
                        {
                                        return $e->getMessage();
                        }

                }
                             //ADD GET PARAM
                function addGetParamFromUrl(&$url, $varName, $value)
                {
                        try
                        {
                                // is there already an ?
                                if (strpos($url, "?"))
                                {
                           $str = $url  . "&" . $varName . "=" . $value;
                                }
                                else
                                {
                           $str = $url  . "?" . $varName . "=" . $value;

				
                                }
                                return $str;
                        }
                        catch(Exception $e)
                        {
                                return $e->getMessage();
                        }
                }


		//BULK KEEPLIST DATA DOWNLOAD
                function bulkDownloadKeep($propUrl,$mediaUrl,$accessToken,$datafile,$mediafile,$postmantoken,$media=false,$offset=false)
                {
                        try
                        {
                                $propArr                                                =               array();
                                                                $listArr                        =       array();
                                print "PROP URL IS: ".$propUrl."\n";
                                $jsonArr                        =       array();
                                $prop_json                      =       $this->getDataByCurl($propUrl,$accessToken,$datafile,$postmantoken);
                                $jsonArr                        =       json_decode($prop_json,true);
                                //offset
                                $cntr                           =       1000;

                                $path_parts                     =       pathinfo($datafile);
				print "OFFSET is :".$offset."\n";

                                if($offset==true)
                                {
$datafile               =       $path_parts['dirname']."/".$path_parts['filename']."_".$cntr.".json";
                                        print "FILE IS: ".$datafile."\n";
                                        $file                   =       fopen($datafile,"w");
                                }
                                else
                                {
                                        $file                   =       fopen($datafile,"w");
                                }

                                //print_r($jsonArr['value']);

                                for($i=0;$i<count($jsonArr['value']);$i++)
                                {
                                        $listArr[] = $jsonArr['value'][$i]['ListingId'];
					 $propArr[] = $jsonArr['value'][$i]['ListingId'];
                                }

                                //print_r($listArr);

                                if($media==true)
                                {
                                        $fileMedia              =       fopen($mediafile,"w");
                                        $prop_media             =       $this->getMediaByCurl($mediaUrl,$accessToken,$mediafile,$postmantoken,$listArr);
                                        print_r($prop_media);
                                        fwrite($fileMedia,$prop_media);
                                        fclose($fileMedia);
                                }


                                fwrite($file,$prop_json);
                                fclose($file);
                                //print_r($jsonArr);
				$nextlink               =       $jsonArr['@odata.nextLink'];
                                print "DATA COUNT IS: ".$jsonArr['@odata.count'];
                                echo "NEXT LINK IS :".$nextlink."\n";

                                while($nextlink != "")
                                {
                                        $cntr   =       $cntr+1000;
                                        print "value of CNTR is: ".$cntr."\n";
                                        $nextlink               =       $this->query_param_encode($jsonArr['@odata.nextLink']);
                                        print "NEXT LINK BEFORE: ".$nextlink."\n";

                                        $jsonArr                =       array();
                                        $prop_json              =       $this->getDataByCurl($nextlink,$accessToken,$datafile,$postmantoken);
                                        $jsonArr                =       json_decode($prop_json,true);
					 $listArr                =       array();

                                        for($i=0;$i<count($jsonArr['value']);$i++)
                                        {
                                                $listArr[] =    $jsonArr['value'][$i]['ListingId'];
                                                $propArr[] =    $jsonArr['value'][$i]['ListingId'];
                                        }

                                        print_r($listArr);

                                        if($offset==true)
                                        {
                                                $datafile               =       $path_parts['dirname']."/".$path_parts['filename']."_".$cntr.".json";
						print "DATA FILE IS: ".$datafile."\n";
                                                $file                   =       fopen($datafile,"w");
                                        }
                                        else
                                        {
                                                //$file                 =       fopen($datafile,"w");
                                                $file                   =       fopen($datafile,"a");
                                       }

                                        //WRITE IN FILE
                                        fwrite($file,$prop_json);
                                        fclose($file);

                                        if($media==true)
                                        {
						$prop_media             =       $this->getMediaByCurl($mediaUrl,$accessToken,$mediafile,$postmantoken,$listArr);
                                                $fileMedia              =       fopen($mediafile,"a");
                                                fwrite($fileMedia,$prop_media);
                                                fclose($fileMedia);
                                        }

                                        //print_r($jsonArr);

                                        if(isset($jsonArr['@odata.nextLink']))
                                        {
                                                $nextlink               =       $jsonArr['@odata.nextLink'];
                                        }
                                        else
                                        {
                                                $nextlink               =       "";
					 }
                                        echo "NEXT LINK AFTER: ".$nextlink."\n";

                                }
                        }
                        catch(Exception $e)
                        {
                                return $e->getMessage();
                        }
                                                return $propArr;
                }



		//BULK DATA DOWNLOAD
                function replication($propUrl,$mediaUrl,$accessToken,$datafile,$replicationFILE,$postmantoken,$media=false,$offset=false)
                {
                        try
                        {
                                $propArr                                                =               array();
                                                                $listArr                        =       array();
                                print "PROP URL IS: ".$propUrl."\n";
                                $jsonArr                        =       array();
                                $prop_json                      =       $this->getDataByCurl($propUrl,$accessToken,$datafile,$postmantoken);
                                $jsonArr                        =       json_decode($prop_json,true);
                                //offset
                                $cntr                           =       1000;

                                $path_parts                     =       pathinfo($datafile);
				
                                                                $replication                                    =               fopen($replicationFILE,"w");

                                print "OFFSET is :".$offset."\n";

                                if($offset==true)
                                {
                                        $datafile               =       $path_parts['dirname']."/".$path_parts['filename']."_".$cntr.".json";
                                        print "FILE IS: ".$datafile."\n";
                                        $file                   =       fopen($datafile,"w");
                                }
                                else
                                {
                                        $file                   =       fopen($datafile,"w");
                                }

                                //print_r($jsonArr['value']);

                                for($i=0;$i<count($jsonArr['value']);$i++)
				{
                                        $listArr[] = $jsonArr['value'][$i]['ListingId'];
                                        $propArr[] = $jsonArr['value'][$i]['ListingId'];


   $ListingId = "";

   $OriginatingSystemName = "";
                                                                                $StandardStatus = "";

   $CloseDate = "";

   $ListingId                                              =       $jsonArr['value'][$i]['ListingId'];

   $OriginatingSystemName                  =       $jsonArr['value'][$i]['OriginatingSystemName'];

   $StandardStatus                                 =       $jsonArr['value'][$i]['StandardStatus'];
	$CloseDate                                 =       $jsonArr['value'][$i]['CloseDate'];
                                                                                $urlString = "";

   $urlString.=$ListingId;

   $urlString.="|";

   $urlString.=$OriginatingSystemName;

   $urlString.="|";

   $urlString.=$StandardStatus;
                                                                                $urlString.="|";

   $urlString.=$CloseDate;
	
	$urlString.="|";
                                                                                $urlString.="\n";
                                                                                fwrite($replication,$urlString);

                                }




                                fwrite($file,$prop_json);
                                fclose($file);
                                //print_r($jsonArr);

                                $nextlink               =       $jsonArr['@odata.nextLink'];
                                print "DATA COUNT IS: ".$jsonArr['@odata.count'];

                                echo "NEXT LINK IS :".$nextlink."\n";
				while($nextlink != "")
                                {
                                        $cntr   =       $cntr+1000;
                                        print "value of CNTR is: ".$cntr."\n";
                                        $nextlink               =       $this->query_param_encode($jsonArr['@odata.nextLink']);
                                        print "NEXT LINK BEFORE: ".$nextlink."\n";

                                        $jsonArr                =       array();
                                        $prop_json              =       $this->getDataByCurl($nextlink,$accessToken,$datafile,$postmantoken);
                                        $jsonArr                =       json_decode($prop_json,true);

                                        $listArr                =       array();
					for($i=0;$i<count($jsonArr['value']);$i++)
                                        {
                                                $listArr[] =    $jsonArr['value'][$i]['ListingId'];
                                                $propArr[] =    $jsonArr['value'][$i]['ListingId'];

                                                                                                $ListingId = "";
                                                                                                $OriginatingSystemName = "";
                                                                                                $StandardStatus = "";

                   $CloseDate = "";

                   $ListingId                                              =       $jsonArr['value'][$i]['ListingId'];
			$OriginatingSystemName                  =       $jsonArr['value'][$i]['OriginatingSystemName'];
                                                                                                $StandardStatus                                 =       $jsonArr['value'][$i]['StandardStatus'];
                                                                                                $CloseDate                                 =       $jsonArr['value'][$i]['CloseDate'];
                                                                                                $urlString = "";
                                                                                                $urlString.=$ListingId;

                   $urlString.="|";

                   $urlString.=$OriginatingSystemName;

                   $urlString.="|";
			
		 $urlString.=$StandardStatus;

                   $urlString.="|";
                                                                                                $urlString.=$CloseDate;

                   $urlString.="|";

                   $urlString.="\n";
                                                                                                fwrite($replication,$urlString);
                                        }

                                        //print_r($listArr);

                                        if($offset==true)
                                        {
						 $datafile               =       $path_parts['dirname']."/".$path_parts['filename']."_".$cntr.".json";
                                                print "DATA FILE IS: ".$datafile."\n";
                                                $file                   =       fopen($datafile,"w");
                                        }
                                        else
                                        {
                                                //$file                 =       fopen($datafile,"w");
                                                $file                   =       fopen($datafile,"a");
                                        }

                                        //WRITE IN FILE
                                        fwrite($file,$prop_json);
                                        fclose($file);                        

                                        //print_r($jsonArr);

					
                                        if(isset($jsonArr['@odata.nextLink']))
                                        {
                                                $nextlink               =       $jsonArr['@odata.nextLink'];
                                        }
                                        else
                                        {
                                                $nextlink               =       "";
                                        }
                                        echo "NEXT LINK AFTER: ".$nextlink."\n";

                                }
                        }
                        catch(Exception $e)
                        {
                                return $e->getMessage();
                        }
                                                return $propArr;
				}

}
?>
                                                                            
                                                                            
	


}
