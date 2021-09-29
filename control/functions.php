<?php
    require ROOT_DIR.'/model/author-list.php';

    function getAuthorsCountry($site_id='mx',$siteUrl='',$cdn='',$forced=false){
        $jsonUrl=ROOT_DIR.'/data/'.$site_id.'.json';
        if(!file_exists($jsonUrl) || (date('U') - filectime($jsonUrl)>86400) || $forced===true){
            makeAjsonFile($site_id,$siteUrl,$cdn);
        }
        return $strJsonFileContents = file_get_contents($jsonUrl);
    }

    function validateValues($value,$reference){
        foreach(explode(",", $value->author_type) as $item){
            if(strtolower(str_replace(' ','', $item))===$reference){
                return true;
            }
        }
        return false;
    }

    function getResizedImage($imgRoute,$rootSite){
        $routeCloud = 'https://cloudfront-us-east-1.images.arcpublishing.com/metroworldnews/';
        $routeImg = str_replace($routeCloud,'',$imgRoute);
        $idImg = explode('.',$routeImg)[0];
        $imgObject = json_decode(getAuthorImageFromArc($idImg));
        if(isset($imgObject->additional_properties)){
            return $rootSite.$imgObject->additional_properties->thumbnailResizeUrl;
        }
        return $imgRoute;

    }

    function makeAuthorListByCountry($site_id='mx',$siteUrl='',$cdn='',$type='author'){
        $completeAuthorList = json_decode(getAuthorsListFromArc())->q_results;
        $filterAuthorList = array();
        foreach($completeAuthorList as $value){
            if($value->author_type){
                if(validateValues($value,$site_id) && validateValues($value,$type)){
                    $author = array();
                    $author['id']   = $value->_id;
                    $author['name'] = $value-> byline;
                    $author['image']= "images/avatar.png";
                    $author['url'] = null;
                    if($value->image){
                        $author['image']=getResizedImage($value->image,$cdn);
                    }
                    if($value->bio_page){
                        $author['url']=$value->bio_page;
                    }
                    array_push($filterAuthorList,$author);
                }
            }
        };
        if(count($filterAuthorList) >= 1){
            return($filterAuthorList);
        }else{
            return array('error' => 'No se encontraron autores que cumplieran con lo solicitado (author_type conteniendo: '.$site_id.' y '.$type.').');
        }
        
    }

    function makeHtmlList($site_id,$cdn='',$route='',$forced=false){
        $jsonUrl=ROOT_DIR.'/data/'.$site_id.'.json';
        $jsonList=json_decode(getAuthorsCountry($site_id,$route,$cdn,$forced), true);
        $liString='';
        if(file_exists($jsonUrl) && !$jsonList['error']){
            foreach($jsonList as $author){
                $liString .= '<li>';
                getResizedImage($author['image'],$cdn);
                if($author['url']){
                    $liString .='<a href="'.$route.$author['url'].'" target="_top">';
                }
                $liString .= '<div class="name">
                                <h3>'.$author['name'].'</h3>
                              </div>';
                $liString .= '<div class="flex-center">
                                <div class="relative">
                                    <div class="colons">
                                        <svg class="MuiSvgIcon-root" focusable="false" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"></path></svg>
                                    </div>
                                    <img src="'.$author['image'].'"/>
                                </div>
                            </div>';
                if($author['url']){
                    $liString .='</a>';
                }
                $liString .='</li>';
            }
            return('<ul class="authors gallery js-flickity" data-flickity-options=\'{ "wrapAround": true }\'>'.$liString.'</ul>');
        }
        if(file_exists($jsonUrl) && $jsonList['error']){
            return '<p><b>ERROR:</b>'.$jsonList['error'].'</p>';
        }
        return '<p>'.$jsonUrl.' archivo no existe</p>';
    }
    
    function makeAjsonFile($site_id,$siteUrl='',$cdn=''){
        $fileUrl = ROOT_DIR.'/data/'.$site_id.'.json';
        if(!file_exists($fileUrl) || (date('U') - filectime($fileUrl)>86400) || $forced===true){

            $fp = fopen($fileUrl,'w');
            fwrite($fp, json_encode(makeAuthorListByCountry($site_id,$siteUrl,$cdn)));
            fclose($fp);
            
        }//else{
            //echo 'archivo existe para '.$site_id;
        //}
    }
?>