<?php
/**
 * WebHook Plugin for LimeSurvey
 * Use question text to create a report and send it by email.
 *
 * @author Matthew Cohen <mccandu@gmail.com>
 * @license GPL v3
 * @version 1.0.1
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
class CanduValue2Attr extends PluginBase {
    protected $storage = 'DbStorage';    
    static protected $description = 'Copy the value of a question answer to a participant attribute on completion of the survey.';
    static protected $name = 'CanduValue2Attr';
    private $debug = true; // true/false
    
    public function __construct(PluginManager $manager, $id) 
    {
        parent::__construct($manager, $id);
        $this->subscribe('afterSurveyComplete');
        $this->subscribe('beforeSurveySettings');
        $this->subscribe('newSurveySettings');
    }
    public function beforeSurveySettings()
    {
        $event = $this->event;
        $event->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => array(
                'webhookurlinfo' => array(
                    'type' => 'info',
                    'content' => '<h4>Description</h4><p>Copy the value of a question answer to a participant attribute on completion of the survey.</p><h4>Instructions</h4><p>Set the source question id (e.g Q00001) and target attibute field (e.g attribute_1) below.</p>'
                ),
                'cv2aField'=>array(
                    'type'=>'string',
                    'label'=>'Question Code (e.g Q00001)',
                    'help'=>'',
                    'current' => $this->get('cv2aField', 'Survey', $event->get('survey'),$this->get('cv2aField',null,null)),
                ),
                'cv2aAttr'=>array(
                    'type'=>'string',
                    'label'=>'Attribute Field (e.g attribute_1)',
                    'help'=>'',
                    'current' => $this->get('cv2aAttr', 'Survey', $event->get('survey'),$this->get('cv2aAttr',null,null)),
                )
            )
         ));
    }
    public function newSurveySettings()
    {
        $event = $this->event;
        foreach ($event->get('settings') as $name => $value)
        {
            $default=$event->get($name,null,null,isset($this->settings[$name]['default'])?$this->settings[$name]['default']:NULL);
            $this->set($name, $value, 'Survey', $event->get('survey'),$default);
        }
    }
    public function afterSurveyComplete() 
    {
      
        $event      = $this->event;
        $surveyId   = $event->get('surveyId');
        $responseId = $event->get('responseId');
        
        $response  = $this->pluginManager->getAPI()->getResponse($surveyId, $responseId);
        $cv2aField = $this->get('cv2aField', 'Survey', $surveyId);
        $cv2aAttr = $this->get('cv2aAttr', 'Survey', $surveyId);

        if(isset($cv2aField) and isset($cv2aAttr) and isset($response['token'])){
            if(isset($response[$cv2aField])){
                $participant = $this->pluginManager->getAPI()->getToken($surveyId, $response['token']);
                if(array_key_exists($cv2aAttr,$participant->attributes)){
                    $oToken=Token::model($surveyId)->find("token=:token",array(":token"=>$response['token']));
                    $oToken->$cv2aAttr=$response[$cv2aField];
                    $oToken->save();
                }
            }
        }

    }
 
}