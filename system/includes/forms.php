<?php 


class CC_HTMLElement {
  private $name;
  private $class;
  protected $value=null;
  
  public function __construct($name, $class) {
    $this->name=$name;
    $this->class=$class;
  }
  
  public function __toString() {
    if (is_bool($this->value)) {
      if ($this->value) return "1"; else return "0";
    }
    return $this->getValue();
  }

  public function getName() {
    return $this->name;
  }
  public function getClass() {
    return $this->class;
  }  
  
  public function setValue($val) {
    $this->value=$val;
  }
  public function getValue() {
    return $this->value;
  }  
  
}

class CC_Field extends CC_HTMLElement {
  private $fieldType="INPUT_OPTIONAL";
  private $label;
  private $form;
  private $error=null;
  
  public function __construct($form, $name, $class, $fieldType, $label) {
    parent::__construct($name, $class);
    $this->form=$form;
    $this->fieldType=$fieldType;
    $this->label=$label;
  }
  
  public function getLabel() {
    return $this->label;
  }
  public function isRequired() {
    return (($this->fieldType=="INPUT_REQUIRED") || ($this->fieldType=="EMAIL") || ($this->fieldType=="PASSWORD"));
  }

  public function getFieldType() {
    return $this->fieldType;
  }

  // Bei Checkbox ist hier "on" oder 1 erlaubt
  public function setValue($val) {
    if ($this->fieldType=="CHECKBOX") {
      if (($val=="on") || ($val==1))
        $this->value=true;
      else $this->value=false;
    }
    else $this->value=$val;
      
  }
  
  public function isValid() {
    if (($this->isRequired()) && (isset($this->value)) && ($this->value=="")) {
      $this->error="Bitte das Feld ausf&uuml;llen!"; 
      return false;
    }
    else if ($this->fieldType=="EMAIL") {
      if (!strpos($this->value,'@')) {
        $this->error="Bitte eine g&uuml;ltige EMail-Adresse angeben";
        return false;                 
      }
    }
    return true;    
  }
  
  public function setError($txt) {
    $this->error=$txt;
  }
  
  public function render() {   
    $txt="";
    if (($this->fieldType=="INPUT_REQUIRED") || ($this->fieldType=="INPUT_OPTIONAL") || ($this->fieldType=="TEXTAREA")
         || ($this->fieldType=="EMAIL") || ($this->fieldType=="PASSWORD")) {     
      $txt.='<label for="'.$this->form->getName().'_'.$this->getName().'">'.$this->getLabel();
        if ($this->isRequired()) $txt.=' <span class="required">*</span>';
      $txt.='</label>';
      
      $txt.='<div class="control-group '.$this->getClass();
      
      if ($this->error!=null)
        $txt.=" error";
        
      $txt.='">';
      
      if ($this->fieldType=="TEXTAREA") {
        $txt.='<textarea rows=12 class="span12" name="'.$this->form->getName().'['.$this->getName().']" id="'.$this->form->getName().'_'.$this->getName().'">';
        if (isset($this->value)) $txt.=$this->value;
        $txt.='</textarea>';        
      }
      else { //INPUT, EMAIL oder PASSWORT
        $txt.='<input class="span3" name="'.$this->form->getName().'['.$this->getName().']" id="'.$this->form->getName().'_'.$this->getName().'" ';
        if ($this->fieldType=="PASSWORD")
          $txt.='type="password" ';
        else
          $txt.='type="text" '; 
        
        if (isset($this->value)) $txt.=' value="'.$this->value.'" ';
        
        $txt.='/>';
      }
      
      if (($this->error!=null)) 
        $txt.='<span class="help-inline error">'.$this->error.'</span>';
        
      $txt.='</div>';
    }
    else if ($this->fieldType=="CHECKBOX"){
      $txt.='<label class="checkbox" for="'.$this->form->getName().'_'.$this->getName().'"">';
      $txt.='<input name="'.$this->form->getName().'['.$this->getName().']" id="'.$this->form->getName().'_'.$this->getName();
          $txt.='" type="checkbox"';
      if (($this->value!=null) && ($this->value)) {
        $txt.=" checked";
      }
      $txt.='/> '.$this->label.'</label>';
          }
    else return "FieldType $this->fieldType not implemented!";
    return $txt;          
  }
}


class CC_Button extends CC_HTMLElement {
  private $label;
  private $icon;
  
  public function __construct($name, $label, $icon) {
    parent::__construct($name, "btn");
    $this->label=$label;
    $this->icon=$icon;
  }
    
  public function render() {
    return '<button class="'.$this->getClass().'" type="submit" name="'.$this->getName().'">'.
           '<i class="icon-'.$this->icon.'"></i> '.$this->label.'</button>';
    
  }  
}


class CC_Model {
  public $fields = array();
  private $buttons = array();
  private $validator = null;
  private $name;
  private $header_big; 
  private $header_small;
  private $help_url;
  
  public $FieldTypes = array("TEXTAREA", "INPUT_REQUIRED", "INPUT_OPTIONAL", "EMAIL", "PASSWORD", "CHECKBOX");
  
  public function __construct($name, $validator, $help_url=null) {
    $this->name=$name;
    $this->validator=$validator;
    $this->help_url=$help_url;
  }  
  
  public function addField($name, $class, $fieldType, $label="") {
    $field=new CC_Field($this, $name, $class, $fieldType, $label);
    if (!in_array($fieldType, $this->FieldTypes))
      echo("FieldTyp $fieldType nicht vorhanden!");
    $this->fields[$name]=$field;     
  }  
  
  public function getName() {
    return $this->name;
  }
  
  public function addButton($label, $icon) {
    array_push($this->buttons,new CC_Button("btn_".count($this->buttons), $label, $icon));    
  }
  
  public function setHeader($big, $small=null) {
    $this->header_big=$big;
    $this->header_small=$small;
    
  }
  
  public function setValidator($validator) {
    $this->validator=$validator;
  }
  
  public function render() {
    global $q_orig;
    
    // Pr�fe ob schon Daten da sind
    if (isset($_POST[$this->getName()])) {
      foreach ($this->fields as $field) {
        if ($field->getFieldType()=="CHECKBOX")
          $field->setValue("off");          
      }
      
      // Hole sie nun rein
      foreach ($_POST[$this->getName()] as $key=>$val) {
        $this->fields[$key]->setValue($val);
      }      
    
      // Validiere Daten
      $isValid=true;
      foreach ($this->fields as $field) {
        if (!$field->isValid())
          $isValid=false;
      }
      if ($isValid) {
        if ($this->validator==null)
          return "no validator given!";
        $ret=call_user_func($this->validator,$this);
//        if ($ret!=true)
//          return $ret;       
      }
    }
    
    // Nun wird gerendert!
    $txt="";
    
    if ($this->header_big!=null)
      $txt.="<h1>$this->header_big</h1>";
    if ($this->header_small!=null)
      $txt.="<p>$this->header_small</p>";
    
    
    $txt.='<div class="form">';
    $txt.='<form class="well form-vertical" id="verticalForm" action="?q='.$q_orig.'" method="post">';
    if ($this->help_url!=null) {
      $txt.='<label class="ct_help_label"><a title="Hilfe aufrufen" href="http://intern.churchtools.de?q=help&doc='.$this->help_url.'" target="_clean">';
      $txt.='<i class="icon-question-sign"></i></a>';
      $txt.='</label>';
    }
      
    $one_required=false;
    // Render die Felder
    foreach ($this->fields as $field) {
      $txt.=$field->render();
      if ($field->isRequired())
        $one_required=true;
    }     
    
    foreach ($this->buttons as $button) {
      $txt.=$button->render()."&nbsp;";
    }
    
    $txt.='</form>';
    
    if ($one_required)
      $txt.='<p class="note">Felder mit <span class="required">*</span> m&uuml;ssen ausgef&uuml;llt werden.</p>';
    
    
    
    $txt.='</div>';
    return $txt;
  }
}


?>