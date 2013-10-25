<?php
/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2013
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2013, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */
?>



<h3>Summary</h3>
<h3 class="episodeTitle"><?php echo $episode->support_services ? 'Support services' : $episode->firm->getSubspecialtyText() ?></h3>


<?php

function showCustomEpisode($episodeSummary) {
  $episodes = Yii::app()->params['episode_summaries'];
  $subspecialty = $episodeSummary->firm->serviceSubspecialtyAssignment->subspecialty;
  $elements = $episodes[$subspecialty->name]['layout'];
  // get each element, where the element is the last defined one in the episode:
  foreach ($elements as $id => $element) {
    // at this time, two definitions are possible - an event type-based
    // configuration, where each element is loaded individually; or 
    // (see below) a basic view is loaded:
    if (isset($element['event_type'])) {
      // class load the event type API:
      $class_name = $element['event_type'] . '_API';
      $path = $element['event_type'] . '.components.' . $class_name;
      Yii::import($path, true);
      $api = new $class_name();
      $last_et = $api->getElementForLatestEventInEpisode($episodeSummary->patient, $episodeSummary->episode, $element['class_name']);
      $var_name = 'element';
      if ($last_et && ElementType::model()->find('class_name=:class_name', array(':class_name' => $element['class_name']))) {
        $p = array($var_name => $last_et);
        if (isset($element['params'])) {
          foreach ($element['params'] as $name => $param) {
            $p = array_merge($p, array($name => $param));
          }
        }
        $view = $element['event_type'] . '.views.default._view_' . $element['class_name'];
        if (isset($element['view'])) {
          $view = $element['event_type'] . '.views.default.' . $element['view'];
        }
        if (isset($element['css_prefix'])) {
          echo $element['css_prefix'];
        }
        $date = '';
        if (isset($element['show_date'])) {
          $date = $last_et->last_modified_date;
        }
        echo '<h4 class="elementTypeName">' . $id . ' (' . $date . ')</h4>';
        $episodeSummary->renderPartial($view, $p);
        if (isset($element['css_suffix'])) {
          echo $element['css_suffix'];
        }
      }
    } else {
      // it's a basic view:
      echo '<h4 class="elementTypeName">' . $id . '</h4>';
      $episodeSummary->renderPartial($element['view'], array());
    }
  }
}

/* Custom episodes are episides that decide to define their own (config-defned)
 * layout, rather than having a summary in views/clinical/episodeSummaries:
 */
$custom_episodes = isset(Yii::app()->params['episode_summaries']);


if ($custom_episodes) {
  $subspecialty = $episode->firm->serviceSubspecialtyAssignment->subspecialty;
  try {
    $episode_views = Yii::app()->params['episode_summaries'][$subspecialty->name]['order'];
  } catch(Exception $e) {
    // config not defined, move on
  }
}
// if no user-defined config, just display the main (default) summary:
if (!$episode_views) {
  $episode_views = array('summary');
}
foreach ($episode_views as $ep_view) {
  if ($ep_view === 'custom') {
    showCustomEpisode($this);
  }
  if ($ep_view == 'firm') {
    try {
      /* This is where, if custom episodes are set, they are dynamically loaded.
       * TODO they're very simplistic at the moment, and there are issues loading
       * views from modules that contain other views within the same module. */
      if ($episode->firm) {
        echo $this->renderPartial('/clinical/episodeSummaries/' . $episode->firm->getSubspecialtyID(), array('episode' => $episode));
      }
    } catch (Exception $e) {
      // If there is no extra episode summary detail page for this subspecialty we don't care
    }
  }
  if ($ep_view === 'summary') {
    if (!empty($episode)) {
      if ($episode->diagnosis) {
        $eye = $episode->eye ? $episode->eye->name : 'None';
        $diagnosis = $episode->diagnosis ? $episode->diagnosis->term : 'none';
      } else {
        $eye = 'No diagnosis';
        $diagnosis = 'No diagnosis';
      }
      $episode->audit('episode summary', 'view', false);
      ?>
      <?php $this->renderPartial('//base/_messages'); ?>

      <h4>Principal diagnosis:</h4>

      <div class="eventHighlight big">
        <h4><?php echo $episode->diagnosis ? $episode->diagnosis->term : 'None' ?></h4>
      </div>

      <h4>Principal eye:</h4>

      <div class="eventHighlight big">
        <h4><?php echo $episode->eye ? $episode->eye->name : 'None' ?></h4>
      </div>

      <!-- divide into two columns -->
      <div class="cols2 clearfix">
        <div class="left">
          <h4>Start Date</h4>
          <div class="eventHighlight">
            <h4><?php echo $episode->NHSDate('start_date') ?></h4>
          </div>
        </div>

        <div class="right">
          <h4>End date:</h4>
          <div class="eventHighlight">
            <h4><?php echo!empty($episode->end_date) ? $episode->NHSDate('end_date') : '(still open)' ?></h4>
          </div>
        </div>

        <div class="left">
          <h4>Subspecialty:</h4>
          <div class="eventHighlight">
            <h4><?php echo $episode->support_services ? 'Support services' : $episode->firm->getSubspecialtyText() ?></h4>
          </div>
        </div>

        <div class="right">
          <h4>Consultant firm:</h4>
          <div class="eventHighlight">
            <h4><?php echo $episode->firm ? $episode->firm->name : 'None' ?></h4>
          </div>
        </div>
      </div> <!-- end of cols2 (column split) -->

      <?php
    } else {
      // hide the episode border 
      ?>
      <script type="text/javascript">
        $('div#episodes_details').hide();
      </script>
      <?php
    }
  }
}
?>

<div class="metaData">
  <span class="info"><?php echo $episode->support_services ? 'Support services' : $episode->firm->getSubspecialtyText() ?>: created by <span class="user"><?php echo $episode->user->fullName ?> on <?php echo $episode->NHSDate('created_date') ?> at <?php echo substr($episode->created_date, 11, 5) ?></span></span>
</div>

<!-- Booking -->

<h4>Episode Status</h4>

<div class="eventHighlight big">
  <h4><?php echo $episode->status->name ?></h4>
</div>

<div class="metaData">
  <span class="info">Status last changed by <span class="user"><?php echo $episode->usermodified->fullName ?> on <?php echo $episode->NHSDate('last_modified_date') ?> at <?php echo substr($episode->last_modified_date, 11, 5) ?></span></span>
</div>

<div class="eventData">
  <?php
  foreach (EventType::model()->getEventTypeModules() as $event_type) {
    if ($api = $event_type->api) {
      if ($eventData = $api->getEpisodeHTML($episode->id)) {
        ?>
        <div>
          <?php echo $eventData ?>
        </div>
        <?php
      }
    }
  }
  ?>
</div>

<script type="text/javascript">
  $('#closelink').click(function() {
    $('#dialog-confirm').dialog({
      resizable: false,
      height: 140,
      modal: false,
      buttons: {
        "Close episode": function() {
          $.ajax({
            url: $('#closelink').attr('href'),
            type: 'GET',
            success: function(data) {
              $('#episodes_details').show();
              $('#episodes_details').html(data);
            }
          });
          $(this).dialog('close');
        },
        Cancel: function() {
          $(this).dialog('close');
        }
      },
      open: function() {
        $(this).parents('.ui-dialog-buttonpane button:eq(1)').focus();
      }
    });
    return false;
  });
</script>

<?php if (empty($episode->end_date)) { ?>
  <div style="margin-top:40px; text-align:right; position:relative; ">
    <!--button id="close-episode" type="submit" value="submit" class="wBtn_close-episode ir">Close Episode</button-->

    <div id="close-episode-popup" class="popup red" style="display: none;">
      <p style="text-align:left;">You are closing this episode. This can not be undone. Once an episode is closed it can not be re-opened.</p>
      <p><strong>Are you sure?</strong></p>
      <div class="action_options">
        <span class="aBtn"><a id="yes-close-episode" href="#"><strong>Yes, I am</strong></a></span>
        <span class="aBtn"><a id="no-close-episode" href="#"><strong>No, cancel this.</strong></a></span>
      </div>
    </div>
  </div>

  <script type="text/javascript">
    $('#close-episode').unbind('click').click(function(e) {
      e.preventDefault();
      $('#close-episode-popup').slideToggle(100);
      return false;
    });

    $('#no-close-episode').unbind('click').click(function(e) {
      e.preventDefault();
      $('#close-episode-popup').slideToggle(100);
      return false;
    });

    $('#yes-close-episode').unbind('click').click(function(e) {
      e.preventDefault();
      $('#close-episode-popup').slideToggle(100);
      $.ajax({
        url: '<?php echo Yii::app()->createUrl('clinical/closeepisode/' . $episode->id) ?>',
        success: function(data) {
          $('#event_content').html(data);
          return false;
        }
      });

      return false;
    });
  </script>
<?php } ?>
