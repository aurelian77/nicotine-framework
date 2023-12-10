<?php
declare(strict_types=1);

namespace nicotine;

/**
| Support for HTML forms.
*/
class Form {

    /**
    | Output form tags in XHTML format.
    */
    public bool $isXHTML = true;

    /**
    | Current tag, open [and close] tag.
    */
    private string $startTag = '';

    /**
    | Some tags are closed as "</tag>" instead of " />".
    */
    private bool $hasEndTag = false;

    /**
    | Current tag attributes.
    */
    private array $attributes = [];

    /**
    | Some tags like <label>text</label> contains text inside them.
    */
    private string $text = '';

    /**
    | For <select>.
    | Content type for building <option>s of the select.
    | @example flat | groupFlat | multi | groupMulti
    */
    private string $content = '';

    /**
    | For <select>.
    | @example on multi and groupMulti ['id', 'name']
    | @example on flat and groupFlat []
    */
    private array $columns = [];

    /**
    | For <select>.
    */
    private array $options = [];

    /**
    | For <select>.
    */
    private array $disabledOptions = [];

    /**
    | For <select>.
    */
    private array $selectedOptions = [];

    /**
    | For <select>.
    */
    public string $emptyValue = '';

    /**
    | For <select>.
    */
    public string $emptyText = '';

    /**
    | Tags and attributes.
    */
    public function __call($method, $arguments)
    {
        switch ($method) {
            case 'open':
                $this->startTag = 'form';
            break;

            case 'close':
                return '</form>'.PHP_EOL;
            break;

            // All tags.
            case 'input':
            case 'form':
            case 'button':
            case 'label':
            case 'textarea':
            case 'select':
                $this->startTag = $method;
            break;

            // All attributes.
            case 'type':
            case 'name':
            case 'for':
            case 'value':
            case 'id':
            case 'autocomplete':
            case 'method':
            case 'action':
            case 'placeholder':
            case 'class':
            case 'style':
            case 'target':
            case 'minlength':
            case 'maxlength':
                $this->attributes[] = $method.'="'.$arguments[0].'"';
            break;

            // data-* attributes.
            case 'data': 
                foreach($arguments[0] as $key => $value) {
                    $this->attributes[] = 'data-'.$key.'="'.$value.'"';
                }
            break;

            // Boolean attributes.
            case 'checked':
            case 'selected':
            case 'disabled':
            case 'readonly':
            case 'multiple':
            case 'required':
                // Without argument, on logical calcs, need to rechain methods.
                if ($arguments[0] == true) {
                    if ($this->isXHTML == true) {
                        $this->attributes[] = $method.'="'.$method.'"';
                    } else {
                        $this->attributes[] = $method;
                    }
                }
            break;

            // Full & custom attributes.
            case 'custom':
                $this->attributes[] = $arguments[0];
            break;

            // Inner text.
            case 'text':
                $this->text = $arguments[0];
            break;

            // Content type.
            case 'content':
                $this->content = $arguments[0];
            break;

            case 'options':
                $this->options = $arguments[0];
            break;

            case 'disabledOptions':
                $this->disabledOptions = $arguments[0];
            break;

            case 'selectedOptions':
                $this->selectedOptions = $arguments[0];
            break;

            case 'emptyValue':
                $this->emptyValue = $arguments[0];
            break;

            case 'emptyText':
                $this->emptyText = $arguments[0];
            break;

            case 'columns':
                $this->columns = $arguments;
            break;

            // $_SESSION['csrf'] = '' by default.
            case 'csrf':
                print '<input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'"'.($this->isXHTML ? ' /' : '').'>'.PHP_EOL;
                return;
            break;
        }

        // Tags with end tag.
        if (in_array($method, [
            'textarea',
            'button',
            'label',
            'select',
            // <form> uses ->close()
        ])) {
            $this->hasEndTag = true;
        }

        return $this;
    }

    /**
    | On print chained()->methods();
    */
    public function __toString()
    {
        // Prepare the output.
        $return = '<'.$this->startTag;

        if (!empty($this->attributes)) {
            $return .= ' '.implode(' ', $this->attributes);
        }

        if ($this->startTag == 'form' && in_array('method="post"', $this->attributes)) {
            $return .= ' enctype="multipart/form-data"';
        }

        if (!in_array($this->startTag, ['form', 'select']) && $this->isXHTML == true && $this->hasEndTag == false) {
            $return .= ' /';
        }

        $return .= '>';

        if ($this->startTag == 'select') {
            $this->text = $this->getOptions();
        }

        if ($this->hasEndTag == true) {
            $return .= $this->text;
            $return .= '</'.$this->startTag.'>'.PHP_EOL;
        }

        // Reset $this values.
        $this->startTag = '';
        $this->hasEndTag = false;
        $this->attributes = [];
        $this->text = '';
        $this->content = '';
        $this->options = [];
        $this->disabledOptions = [];
        $this->selectedOptions = [];
        $this->emptyValue = '';
        $this->emptyText = '';
        $this->columns = [];

        // The output.
        return $return;
    }

    private function getOptions()
    {
        $return = [];

        if (!empty($this->emptyText)) {
            $return[] = "\t".'<option value="'.$this->emptyValue.'">'.$this->emptyText.'</option>';
        }

        $disabledString = $this->isXHTML ? ' disabled="disabled"' : ' disabled';
        $selectedString = $this->isXHTML ? ' selected="selected"' : ' selected';

        switch ($this->content) {
            case 'flat':
                foreach ($this->options as $key => $value) {
                    $return[] = "\t".'<option value="'.$key.'"'.(
                        in_array($key, $this->selectedOptions) ? $selectedString : ''
                    ).(
                        in_array($key, $this->disabledOptions) ? $disabledString : ''
                    ).'>'.$value.'</option>';
                }
            break;

            case 'groupFlat':
                foreach ($this->options as $groupLabel => $groupFlat) {
                    $return[] = "\t".'<optgroup label="'.$groupLabel.'">';

                        foreach ($groupFlat as $key => $value) {
                            $return[] = "\t\t".'<option value="'.$key.'"'.(
                                in_array($key, $this->selectedOptions) ? $selectedString : ''
                            ).(
                                in_array($key, $this->disabledOptions) ? $disabledString : ''
                            ).'>'.$value.'</option>';
                        }

                    $return[] = "\t".'</optgroup>';
                }
            break;

            case 'multi':
                foreach ($this->options as $array) {
                    $key = $array[$this->columns[0]];
                    $value = $array[$this->columns[1]];

                    $return[] = "\t".'<option value="'.$key.'"'.(
                        in_array($key, $this->selectedOptions) ? $selectedString : ''
                    ).(
                        in_array($key, $this->disabledOptions) ? $disabledString : ''
                    ).'>'.$value.'</option>';
                }
            break;

            case 'groupMulti':
                foreach ($this->options as $groupLabel => $groupArray) {
                    $return[] = "\t".'<optgroup label="'.$groupLabel.'">';

                        foreach ($groupArray as $array) {
                            $key = $array[$this->columns[0]];
                            $value = $array[$this->columns[1]];

                            $return[] = "\t\t".'<option value="'.$key.'"'.(
                                in_array($key, $this->selectedOptions) ? $selectedString : ''
                            ).(
                                in_array($key, $this->disabledOptions) ? $disabledString : ''
                            ).'>'.$value.'</option>';
                        }

                    $return[] = "\t".'</optgroup>';
                }
            break;
        }

        return PHP_EOL.implode(PHP_EOL, $return).PHP_EOL;
    }
}