<?php

namespace Drupal\example_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\file\Entity\File;

/**
 * Example Code Progress Controller
 * @todo - Can be deleted if already done using views
 */

class SchoolProgress extends ControllerBase {
  /**
  * Function to return dashboard content
  */
  
  public function content($type = 'personal', $id = 0) {
    $id = ($id == 0) ? \Drupal::currentUser() -> id() : $id;
    
    $content = '<div class="panel-group" id="accordion">';
    switch ($type) {
      case 'school' :
        //Overall school progress
        $content .= '<div class="panel panel-default"><div class="panel-heading"><h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#school-overall">' . $this -> t('Overall') . '</a></h4></div>';
        
        //'Overall' Accordian group start
        $content .= '<div id="school-overall" class="panel-collapse collapse in">';
        
        $eq_stars_summary = calculate_eq_stars('school', $id);
        $content .= '<div class="eq-stars-summary">' . $this -> t('Your students have a total of <span class="obtained">@obtained</span>/<span class="total">@total</span> EQ Stars!', [
          '@obtained' => isset($eq_stars_summary['obtained']) ? $eq_stars_summary['obtained'] : 0,
          '@total' => isset($eq_stars_summary['total']) ? $eq_stars_summary['total'] : 0,
        ]) . '</div>';
        
        $tests_summary = calculate_tests_average('school', $id);
        $tests_average = round(($tests_summary['obtained'] / $tests_summary['total']) * 100);
        
        $content .= '<div class="tests-summary">' . $this -> t('Tests Average<br><span>@percent%</span>', [
          '@percent' => $tests_average,
        ]) . '</div>';
        
        $completed_video_tests = calculate_videos_tests('school', $id);
        
        $content .= '<div class="row"><div class="col-md-6 col-sm-6 col-xs-6 completed-videos"><div>' . $this -> t('Completed Videos') . '</div><div class="progress-circle"><div class="c100 p' . $completed_video_tests['completed_videos'] . '"><span>' . $this -> t('@completed_videos%', [
          '@completed_videos' => $completed_video_tests['completed_videos'],
        ]) . '</span></div></div></div>';
        
        $content .= '<div class="col-md-6 col-sm-6 col-xs-6 completed-tests"><div>' . $this -> t('Completed Tests') . '</div><div class="progress-circle"><div class="c100 p' . $completed_video_tests['completed_tests'] . '"><span>' . $this -> t('@completed_tests%', [
          '@completed_tests' => $completed_video_tests['completed_tests'],
        ]) . '</span></div></div></div></div>';
        
        //'Overall' Accordian group end
        $content .= '</div>';
        
        //Teacher wise school progress
        $school_teachers_db = \Drupal::database();
        $school_teachers_query = $school_teachers_db -> select('profile__field_teacher_school', 'fts');
        $school_teachers_query -> join('profile', 'p', 'p.profile_id = fts.entity_id');
        $school_teachers_query -> condition('fts.field_teacher_school_target_id', $id);
        $school_teachers_query -> fields('p', ['uid']);
        $school_teachers = $school_teachers_query -> execute() -> fetchAllAssoc('uid');
        
        foreach ($school_teachers as $teacher_uid => $teacher) {
          $teacher_user = \Drupal::entityTypeManager() -> getStorage('user') -> load($teacher_uid);
          $teacher_full_name = '';
          $first_name = $teacher_user -> get('field_first_name') -> getValue();
          $last_name = $teacher_user -> get('field_last_name') -> getValue();
          $teacher_full_name .= isset($first_name[0]) ? $first_name[0]['value'] : $teacher_full_name;
          $teacher_full_name .= isset($last_name[0]) ? ' ' . $last_name[0]['value'] : $teacher_full_name;
          $username = $teacher_user -> name -> value;
          $teacher_full_name = (trim($teacher_full_name) == '') ? $username : $teacher_full_name;
          
          //'Teacher' Accordian group start
          $content .= '<div class="panel-heading"><h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="' . $username . '">' . $teacher_full_name . '</a></h4></div><div id="' . $username . '" class="panel-collapse collapse in">';
          
          $teacher_overall = calculate_tests_average('teacher', $teacher_uid, $id);
          
          $teacher_overall_percent = ($teacher_overall['obtained'] > 0 && $teacher_overall['total'] > 0) ? round(($teacher_overall['obtained'] / $teacher_overall['total']) * 100) : 0;
          
          $content .= '<div class="teacher-overall-tests"><span>' . $this -> t('ALL CLASSES GRADE: ') . '</span>' . $this -> t('@teacher_overall_percent%', [
            '@teacher_overall_percent' => $teacher_overall_percent,
          ]) . '</div>';
          
          $teacher_classes_db = \Drupal::database();
          $teacher_classes_query = $teacher_classes_db -> select('profile__field_teacher_classrooms', 'ftc');
          $teacher_classes_query -> join('profile', 'p', 'p.profile_id = ftc.entity_id');
          $teacher_classes_query -> join('taxonomy_term_field_data', 'tfd', 'tfd.tid = ftc.field_teacher_classrooms_target_id');
          $teacher_classes_query -> condition('p.uid', $teacher_uid);
          $teacher_classes_query -> fields('ftc', ['field_teacher_classrooms_target_id']);
          $teacher_classes_query -> fields('tfd', ['name']);
          $teacher_classes = $teacher_classes_query -> execute() -> fetchAllAssoc('field_teacher_classrooms_target_id');
          
          $content .= '<div class="classes-overall-tests">';
          
          foreach ($teacher_classes as $class_id => $class) {
            $teacher_class = calculate_tests_average('class', $class_id, $id);
            $teacher_class_percent = ($teacher_class['obtained'] > 0 && $teacher_class['total'] > 0) ? round(($teacher_class['obtained'] / $teacher_class['total']) * 100) : 0;
            
            $content .= '<div class="class-' . $class_id . '-tests"><div><span>' . $class -> name . '</span>' . $this -> t('@teacher_class_percent%', [
              '@teacher_class_percent' => $teacher_class_percent,
            ]) . '</div></div>';
          }
          
          $content .= '</div>';
          
          //'Teacher' Accordian group end
          $content .= '</div>';
        }
        
        break;
        
        case 'teacher' :
        $school_id = \Drupal::database() -> select('profile__field_teacher_school', 'fts');
        $school_id -> join('profile', 'p', 'p.profile_id = fts.entity_id');
        $school_id -> fields('fts', ['field_teacher_school_target_id']);
        $school_id -> condition('p.uid', $id);
        $school_id = $school_id -> execute() -> fetchField();

        //Overall teacher progress
        $content .= '<div class="panel panel-default"><div class="panel-heading"><h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#teacher-overall">' . $this -> t('Overall') . '</a></h4></div>';
        
        //'Overall' Accordian group start
        $content .= '<div id="teacher-overall" class="panel-collapse collapse in">';
        
        $eq_stars_summary = calculate_eq_stars('teacher', $id);
        $content .= '<div class="eq-stars-summary">' . $this -> t('Your students have a total of <span class="obtained">@obtained</span>/<span class="total">@total</span> EQ Stars!', [
          '@obtained' => isset($eq_stars_summary['obtained']) ? $eq_stars_summary['obtained'] : 0,
          '@total' => isset($eq_stars_summary['total']) ? $eq_stars_summary['total'] : 0,
        ]) . '</div>';
        
        $tests_summary = calculate_tests_average('teacher', $id, $school_id);
        $tests_average = ($tests_summary['obtained'] > 0 && $tests_summary['total'] > 0) ? round(($tests_summary['obtained'] / $tests_summary['total']) * 100) : 0;
        
        $content .= '<div class="tests-summary">' . $this -> t('Tests Average<br><span>@percent%</span>', [
          '@percent' => $tests_average,
        ]) . '</div>';
        
        $completed_video_tests = calculate_videos_tests('teacher', $id, $school_id);

        $content .= '<div class="row"><div class="col-md-6 col-sm-6 col-xs-6 completed-videos"><div>' . $this -> t('Completed Videos') . '</div><div class="progress-circle"><div class="c100 p' . $completed_video_tests['completed_videos'] . '"><span>' . $this -> t('@completed_videos%', [
          '@completed_videos' => $completed_video_tests['completed_videos'],
        ]) . '</span></div></div></div>';
        
        $content .= '<div class="col-md-6 col-sm-6 col-xs-6 completed-tests"><div>' . $this -> t('Completed Tests') . '</div><div class="progress-circle"><div class="c100 p' . $completed_video_tests['completed_tests'] . '"><span>' . $this -> t('@completed_tests%', [
          '@completed_tests' => $completed_video_tests['completed_tests'],
        ]) . '</span></div></div></div></div>';
        
        //'Overall' Accordian group end
        $content .= '</div>';
        
        //Class wise teacher progress        
        $teacher_classes_db = \Drupal::database();
        $teacher_classes_query = $teacher_classes_db -> select('profile__field_teacher_classrooms', 'ftc');
        $teacher_classes_query -> join('profile', 'p', 'p.profile_id = ftc.entity_id');
        $teacher_classes_query -> join('taxonomy_term_field_data', 'tfd', 'tfd.tid = ftc.field_teacher_classrooms_target_id');
        $teacher_classes_query -> condition('p.uid', $id);
        $teacher_classes_query -> fields('ftc', ['field_teacher_classrooms_target_id']);
        $teacher_classes_query -> fields('tfd', ['name']);
        $teacher_classes = $teacher_classes_query -> execute() -> fetchAllAssoc('field_teacher_classrooms_target_id');
        
        $content .= '<div class="classes-overall-tests">';
        
        foreach ($teacher_classes as $class_id => $class) {
          //'Teacher' Accordian group start
          $content .= '<div class="panel-heading"><h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#classroom' . $class_id . '">' . $class -> name . '</a></h4></div><div id="classroom' . $class_id . '" class="panel-collapse collapse in">';
          
          $teacher_class = calculate_tests_average('class', $class_id, $school_id);
          $teacher_class_percent = ($teacher_class['obtained'] > 0 && $teacher_class['total'] > 0) ? round(($teacher_class['obtained'] / $teacher_class['total']) * 100) : 0;
          
          $content .= '<div class="classroom-' . $class_id . '-tests"><div><span>' . $this -> t('CLASS GRADE') . '</span><div class="small  c100 p' . $teacher_class_percent . '"><span>' . $this -> t('@teacher_class_percent%', [
            '@teacher_class_percent' => $teacher_class_percent,
          ]) . '</span></div></div></div>';
          
          $class_students_db = \Drupal::database();
          $class_student_query = $class_students_db -> select('profile', 'p');
          $class_student_query -> leftjoin('profile__field_child_school', 'fcs', 'fcs.entity_id = p.profile_id');
          $class_student_query -> leftjoin('profile__field_child_classroom', 'fcc', 'fcc.entity_id = p.profile_id');
          $class_student_query -> condition('fcs.field_child_school_target_id', $school_id);
          $class_student_query -> condition('fcc.field_child_classroom_target_id', $class_id);
          $class_student_query -> fields('p', ['uid']);
          $class_students = $class_student_query -> execute() -> fetchAllAssoc('uid');
          
          $content .= '<div class="students-summary">';
          foreach ($class_students as $student_id => $student) {
            $student_summary = calculate_student_summary($student_id);
            $content .= '<a title="' . $student_summary['name'] . '" href="/progress/personal/' . $student_id . '"><div class="student-summary student-summary-' . $student_id . '">';
            $profile_image_html = '';
            
            if (!empty($student_summary['picture'])) {
              $image_file = File::load($student_summary['picture']['target_id']);
              $image_vars = [
                'style_name' => 'max_width_150',
                'uri' => $image_file -> getFileUri(),
              ];
              
              $profile_image = [
                '#theme' => 'image_style',
                '#style_name' => $image_vars['style_name'],
                '#uri' => $image_vars['uri'],
              ];
              
              $profile_image_html = '<div class="student-picture">' . render($profile_image) . '</div>';
            }
            
            
            $content .= $profile_image_html . '<span class="name">' . $student_summary['name'] . '</span><div class="score-summary"><span class="eq">' . $student_summary['eq_stars'] . '</span><span class="test">' . $student_summary['test_percentage'] . '</span></div></div></a>';
            
          }
          
          $content .= '</div></div>';
        }
        break;
    }

    $content .= '</div>';
    $html = [
      '#type' => 'markup',
      '#markup' => $content,
    ];
    
    return $html;
  }
  
  /**
   * Function to check dashboard access
   */
  
  function access(AccountInterface $account) {
    return AccessResult::allowedIf($account -> isAuthenticated());
  }
}