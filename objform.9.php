<?php
/* Your agreement with CustomWebApps gives you a limited license to access and
 * use this library. You may access the library for your own private or
 * business use. You may not reproduce, copy, or redistribute, whole or in
 * part, any of the library that constitutes the CustomWebApps' library
 * functions without written permission.
 *
 * $Id: objform.9.php3,v 1.9 2020/02/22 18:49:31 rtharp Exp $
 *
 */

/**
 * Form Classes and functions
 *
 * @author Ryan Tharp <rtharp@customwebapps.com>
 * @copyright Copyright (c) 1998-2020, Custom Web Apps
 * @filesource
 * @version 0.9
 * @package form
 */

// Dependencies
cwainclude_once('lib.widget.php'); // widget_load

// Model sample structure
/*
// we need an integer indexed array container
$category_model=array(
  array('name'=>'name'),
  array('name'=>'description','type'=>'textarea'),
  array('name'=>'memberof','type'=>'select'
    'getoptions'=>array('sql'=>'select * from category_categories order by name','value'=>'categoryid','name'=>'name')
  ),
);
*/

function filterModel($model, $group, $xlateType = false) {
  $nModel = array();
  foreach($model as &$ctrl) {
    if (in_array($group, $ctrl['group'])) {
      $nctrl = $ctrl;
      if ($xlateType) {
        //$nctrl['type'] = translateType($ctrl['type'], $xlateType);
      }
      $nModel[] = $nctrl;
    }
  }
  unset($ctrl); // safety
  return $nModel;
}

function filterViewOnlyControls($ctrl) {
  $nModel = array();
  foreach($model as &$ctrl) {
    // is this faster than a map?
    switch($ctrl['type']) {
      case 'hidnum':
      case 'hidden':
      case 'hiddate':
      case 'submit':
      case 'imagesubmit':
      case 'none':
        break;
      default:
        $nModel[] = $ctrl;
    }
  }
  unset($ctrl); // safety
  return $nModel;
}

// details
// if not table mode
//   labelCellOpen.$lprefix.$label.$lsuffix.labelCellClose and sometimes (oldnewrow) fieldCellOpen
//   needs to support {{}}.rowid, labelid, fieldid tags
// basically need some auto config of this for subsystems...
// auto values VS strict override...
$defaultDetailConfig = array(
  // we may want to check labelCellOpen to see if it contains "id="
  //'configKey'       => 'default', // potentially for compiler cache of this config...
  'groupOpenPre'    => '</table>' . "\n" .
    '<table class="objform_table">' . "\n" . '<tr><th align="left" colspan="2"><h3>',
  'groupOpenPost'   => '</h3></th></tr>',
  'groupClose'      => '<tr><td colspan="2"><hr></td></tr>',
  'open'            => '<table border="0" class="objform_table">',
  'close'           => '</table>' . "\n" . "\n",
  // h/v align control can be done here...
  // bgcolor/rowid (defaults to objform_row_ROWCOUNT_PLACEMENT) too
  // if rowid not default, it affects the fieldCellOpen ID ROWID_NAME or ROWID_NAME,NAME,..
  // maybe an auto setting?
  'rowOpen'         => '<tr valign="top"',
  // 'noCloseTr'=>false,
  'rowClose'        => '</tr>' . "\n",
  'singleCellOpen'  => '<td colspan=2 ',
  'singleCellClose' => '</td>',
  // labelprefix/labelsuffix can be baked into these...
  'labelCellOpen'   => '<th',
  'labelCellClose'  => '</th>',
  // ->inlineedit support can go here...
  'fieldCellOpen'   => '<td',
  'fieldCellClose'   => '</td>',
  'validationHeader' => '',
  'validationMarker' => '*',
  'validationFooter' => '',
  // related or unrelated to tag in groupOpen*, not right now
  // this is output related
  'openTag'            => '{{',
  'closeTag'           => '}}',
  'methodsLeft'        => false,
  // 1=auto (3/2), 2=non, 3=right of field, 4 left of field, 5 left of label, 6 right of label
  'validationPlacement' => 1,
  'unifiedMethodCells' => false,
  'headerLinkByCount'  => false,
  'linkOptions'        => false, // false means auto, '' means no
);

// required fields marker is handled by make FIELD_label tag handle it
// TO DO: append handler (for fastout support)
// THINK: figure out if we can remove the label tag and accelerate it inline here
function ToTemplateEngine($model, $vert, $cfg) {
  $lastGroup = false;
  $template = '';
  foreach($model as $c => &$ctrl) {
    if ($vert && ($ctrl['groups'] != $ctrl['groupName'])) {
      if ($lastGroup) {
        $template.=$cfg['groupClose'];
      }
      $lastGroup = $ctrl['groupName'];
      $template.=$cfg['groupOpenPre'] . $lastGroup . $cfg['groupOpenPost'];
    }
    if ($vert) {
      $oldnewrow = true;
      if ($c) {
        $oldnewrow = empty($model[$c - 1]['newRow']) ? true : $model[$c - 1]['newRow'];
      }
      $newrow = empty($ctrl['newRow']) ? false : $ctrl['newRow'];
      // TO DO: if type === 'lookup' then label = ''
      // TO DO: if type === 'checkbox' then wrap in <label> tag for $ctrl['name']
    }

    // FIX ME: handle empty label viewbitmask in !$vert

    $tdopt = $ctrl['tdopt'];

    // open field
    if ($vert) {
      // FIX ME: form->altcolor support (will need rowcnt)
      // FIX ME: manage the id? (form->name.$ctrl['name']_row
      // FIX ME: figure out colspan ? (usually furthest right cell)
      // FIX ME: form->divnotr (if !$oldnewrow)
      //   this can be done by setting IDs appropriate in the template
      // FIX ME: form->csslayout support (use css for alignment and coloring)
      $template .= $cfg['rowOpen'] . $ctrl['rowOpenParams']. '>';
      // FIX ME: good default id for fieldCellOpen $form->name_$ctrl['name']_label
      $template .= empty($ctrl['label']) ?
        ($cfg['labelCellOpen'] . $tdopt . '>' .
          $cfg['openTag'] . $ctrl['name'] . '_label' . $cfg['closeTag'] .
          $cfg['labelCellClose']. $cfg['fieldCellOpen']) :
        ($cfg['singleCellOpen'] . $tdopt . '>');
    } else {
      $template .= $cfg['fieldCellOpen']. $tdopt . '>';
    }

    if ($ctrl['link']) {
      $template .= '<a' . $cfg['linkOptions']. ' href="' . $ctrl['link'] . '">';
    }

    // put field tag
    $template .= $cfg['openTag'] . $ctrl['name'] . '_field' . $cfg['closeTag'];

    if ($ctrl['link']) {
      $template .= '</a>';
    }

    // close field
    if ($vert) {
      // FIX ME: if no tables, only close fieldCellClose if label has chars
      // FIX ME: if tables $form->divnotr support (if !$oldnewrow)
      // newrow documentation:
      // 0=not a new row, 1=new row (Default),
      // 2 like 1 but wrap td tag around it / 2 uses tables for rows

      // do we need close ourself (is this the end of the cell)?
      // if ($newrow === 1)
        // we need to end it, now to figure out which tag to end it with
      // else
        // everything has maxcols now just end cell
      $template .= empty($ctrl['label']) ?
        ($cfg['fieldCellClose'].'>') :
        $cfg['singleCellClose'];
      $template .= $cfg['rowClose'];
    } else {
      $template .= $cfg['fieldCellClose'];
    }

  }
  unset($ctrl); // safety
  return $template;
}

// FIX ME: support Actions: label
// maybe not tag but actually render them here...
function appendMethods($ctrl, $cfg) {
  $template = $cfg['fieldCellOpen'] . '>'. $cfg['openTag'] . '_objform_methods' .
    $cfg['closeTag'] . $cfg['fieldCellClose'];
  return $template;
}

// listing
// FIX ME: do we include actions inside model or pass separately...
function cwamodelToHeaderRow($model, $cfg = false) {
  if ($cfg === false) {
    global $defaultDetailConfig;
    $cfg = $defaultDetailConfig;
  }
  $template = $cfg['rowOpen'] . '>';
  // FIX ME: colspan
  if ($cfg['methodsLeft']) {
    // FIX ME: if we have methods
    $template .= $cfg['labelCellOpen'] . '>' . 'Actions:' . $cfg['labelCellClose'];
  }
  if ($cfg['linkOptions'] === false) {
    $cfg['linkOptions'] = ' title="Sort Column"';
  }
  // we use labels basically
  foreach($model as $c => &$ctrl) {
    if (empty($ctrl['link'])) {
      $ctrl['link'] = $cfg['headerLinkByCount'] ? ($c + 1) : $ctrl['name'];
    }
    //$label = is_array($ctrl['label'])  ? $ctrl['name'] : $ctrl['label'];
    $template .= $cfg['labelCellOpen'] . '>'.
      $cfg['openTag'] . $cfg['name'] . '_label' . $cfg['closeTag'].
      $cfg['labelCellClose'];
  }
  unset($ctrl); // safety
  if (!$cfg['methodsLeft']) {
    $template .= $cfg['labelCellOpen'] . '>' . 'Actions:' . $cfg['labelCellClose'];
  }
  $template .= $cfg['rowClose'];
  return $template;
}


// TO DO: put ->inlineedit support into the tag handler...
function cwamodelToListingRow($model, $cfg = false) {
  if ($cfg === false) {
    global $defaultDetailConfig;
    $cfg = $defaultDetailConfig;
  }
  // could check to see if we output js editor inline support yet...
  // probably doesn't belong here...
  $template = '';

  // check for widget _style handler
  // this might not be able to be in cwamodelToHeaderRow
  //   because individual control params may change the style...
  foreach($model as $c => &$ctrl) {
    $type = $ctrl['type'];
    widget_load($type);
    // THINK: is there a way not to populate the global namespace?
    $fname = 'obj_control_' .  $type . '_style';
    if (function_exists($fname)) {
      //echo "Calling [$fname] [".gettype($fname)."]<br>\n";
      $template .= $fname($ctrl);
    }
  }
  unset($ctrl); // safety

  $template .= $cfg['rowOpen'] . '>';
  // include spot for methods
  // FIX ME: colspan
  // FIX ME: _objform_methods_notable support?
  if ($cfg['methodsLeft']) {
    $template .= appendMethods($ctrl, $cfg);
  }
  $template = ToTemplateEngine($model, false, $cfg);
  if (!$cfg['methodsLeft']) {
    $template .= appendMethods($ctrl, $cfg);
  }
  $template .= $cfg['rowClose'];
  return $template;
}

// ok do we output a string or something more templately
// lets start with a string
function cwamodelToDetail($model, $cfg = false) {
  if ($cfg === false) {
    global $defaultDetailConfig;
    $cfg = $defaultDetailConfig;
  }
  $template = $cfg['open'];
  $template .= ToTemplateEngine($model, true, $cfg);
  $template .= $cfg['close'];
}

// get
function cwamodelFromHTTP($model) {
}

function cwaisValid($valuesRetType) {
}

?>
