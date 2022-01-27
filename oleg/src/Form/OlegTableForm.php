<?php

namespace Drupal\oleg\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides form with tables.
 */
class OlegTableForm extends FormBase {

  /**
   * List of keys for form fields.
   *
   * @const
   */
  const TABLE_HEADER = [
    'Year',
    'Jan',
    'Feb',
    'Mar',
    'Q1',
    'Apr',
    'May',
    'Jun',
    'Q2',
    'Jul',
    'Aug',
    'Sep',
    'Q3',
    'Oct',
    'Nov',
    'Dec',
    'Q4',
    'YTD',
  ];

  /**
   * List of keys for inactive form fields.
   *
   * @const
   */
  const DISABLED_INPUT_KEYS = [
    'Q1',
    'Q2',
    'Q3',
    'Q4',
    'YTD',
  ];

  /**
   * The number of tables in form.
   *
   * @var int
   */
  protected int $tablesCount = 1;

  /**
   * The number of rows in tables.
   *
   * @var int
   */
  protected int $rowsCount = 1;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'oleg_table_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Wrapper for form.
    $form['#prefix'] = '<div id="form-wrapper">';
    $form['#suffix'] = '</div>';
    // Button for adding a row to the table.
    $form['add_row'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add row'),
      '#submit' => ['::addTableRow'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::ajaxUpdateForm',
        'wrapper' => 'form-wrapper',
      ],
    ];
    // Button for adding a table to the form.
    $form['add_table'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add table'),
      '#submit' => ['::addTable'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::ajaxUpdateForm',
        'wrapper' => 'form-wrapper',
      ],
    ];
    // Calling the function for creating a table.
    $this->createTable($form, $form_state);
    // Button for submitting.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::ajaxUpdateForm',
        'wrapper' => 'form-wrapper',
      ],
    ];
    // Connecting libraries to the form.
    $form['#attached']['library'][] = 'oleg/oleg.form';
    return $form;
  }

  /**
   * Provides function for creating a form table.
   */
  public function createTable(&$form, FormStateInterface $form_state) {
    for ($i = 0; $i < $this->tablesCount; $i++) {
      // Table ID.
      $tableId = 'table_' . $i;
      $form[$tableId] = [
        '#type' => 'table',
        '#tree' => TRUE,
        '#title' => 'Table' . ($i + 1),
        '#header' => self::TABLE_HEADER,
      ];
      // Calling the function for creating a row for table.
      $this->createTableRow($tableId, $form, $form_state);
    }
  }

  /**
   * Provides function for creating a row for a table.
   *
   * @param string $tableId
   *   Table ID.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function createTableRow(string $tableId, array &$form, FormStateInterface $form_state) {
    for ($i = $this->rowsCount; $i > 0; $i--) {
      // Row ID.
      $rowId = 'row_' . $i;
      for ($j = 0; $j < count(self::TABLE_HEADER); $j++) {
        // Inactive form field for year name.
        if (self::TABLE_HEADER[$j] === 'Year') {
          $form[$tableId][$rowId][self::TABLE_HEADER[$j]] = [
            '#type' => 'number',
            '#value' => (date('Y') + 1 - $i),
            '#disabled' => TRUE,
          ];
        }
        elseif (in_array(self::TABLE_HEADER[$j], self::DISABLED_INPUT_KEYS, TRUE)) {
          // Inactive form fields for estimated values.
          $form[$tableId][$rowId][self::TABLE_HEADER[$j]] = [
            '#type' => 'number',
            '#step' => 0.01,
            '#value' => $form_state->getValue([
              $tableId,
              $rowId,
              self::TABLE_HEADER[$j]
            ], ''),
            '#disabled' => TRUE,
          ];
        }
        else {
          // Form fields for data entry.
          $form[$tableId][$rowId][self::TABLE_HEADER[$j]] = [
            '#type' => 'number',
          ];
        }
      }
    }
  }

  /**
   * Provides function for adding a row to the table.
   */
  public function addTableRow(array &$form, FormStateInterface $form_state): array {
    $this->rowsCount++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Provides function for adding a table to the form.
   */
  public function addTable(array &$form, FormStateInterface $form_state): array {
    $this->tablesCount++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Provides function for updating a form.
   */
  public function ajaxUpdateForm(array &$form): array {
    return $form;
  }

  /**
   * Provides function for getting entered values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Entered values.
   */
  public function getResultFromForm(FormStateInterface $form_state): array {
    // Getting values.
    $values = $form_state->getValues();
    $result = [];
    for ($i = 0; $i < $this->tablesCount; $i++) {
      // Table ID.
      $tableId = 'table_' . $i;
      // Processing of all values.
      foreach ($values as $key => $element) {
        // Selection of the necessary values from the form.
        if ($key === $tableId) {
          $result[$key] = $element;
        }
      }
    }
    // Processing of values.
    foreach ($result as $tableId => $table) {
      foreach ($table as $rowId => $row) {
        foreach ($row as $key => $value) {
          // Delete calculated values.
          if (in_array($key, self::DISABLED_INPUT_KEYS, TRUE) || $key === 'Year') {
            unset($row[$key]);
          }
        }
        $table[$rowId] = $row;
      }
      $result[$tableId] = $table;
    }
    return $result;
  }

  /**
   * Provides a function to check if the array is a list.
   *
   * @param array $array
   *   The array being checked.
   *
   * @return bool
   *   Returns boolean TRUE if an array isn't a list, FALSE when is.
   */
  public function arrayIsNotList(array $array): bool {
    return array_values($array) !== $array;
  }

  /**
   * Provides a function to check if the two arrays are equal.
   *
   * @param array $array1
   *   The array being checked.
   * @param array $array2
   *   Reference array.
   *
   * @return bool
   *   Returns boolean TRUE if arrays aren't equal, FALSE when is.
   */
  public function isNotEqualArray(array $array1, array $array2): bool {
    // Only items with empty values remain.
    $array1 = array_filter($array1, function ($v) {
      return ('' === $v);
    });
    $array2 = array_filter($array2, function ($v) {
      return ('' === $v);
    });
    return $array1 !== $array2;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Obtaining processed values using the function.
    $result = $this->getResultFromForm($form_state);
    // Processing of values.
    foreach ($result as $tableId => $table) {
      foreach ($table as $rowId => $row) {
        foreach ($row as $key => $value) {
          // Checks if tables are completed for the same period.
          // Starting check from the second table.
          if ($tableId !== 'table_0' &&
            $this->isNotEqualArray($row, $result['table_0'][$rowId]) &&
            $this->rowsCount == 1) {
            $form_state->setErrorByName($tableId, $this->t('The tables are filled for different periods.'));
          }
          // For each table, stored all rows in one array.
          $tables[$tableId][] = $value;
          $index = 0;
          // The intermediate array.
          $buffer[$tableId] = $tables[$tableId];
          // Deleting empty values before the first entered value for each table.
          while (empty($buffer[$tableId][$index]) && $index < count($tables[$tableId]) && $buffer[$tableId][$index] !== '0') {
            unset($buffer[$tableId][$index]);
            $index++;
          }
          // Re-indexed arrays.
          $tablesReindex[$tableId] = array_values($buffer[$tableId]);
          for ($i = 0; $i < count($buffer[$tableId]); $i++) {
            if (empty($tablesReindex[$tableId][$i]) && $tablesReindex[$tableId][$i] !== '0') {
              unset($tablesReindex[$tableId][$i]);
            }
          }
          // Checking the array for each table for gaps using the function.
          foreach ($tablesReindex as $item) {
            if ($this->arrayIsNotList($item)) {
              $form_state->setErrorByName($tableId, $this->t('The row should not contain spaces between months'));
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Obtaining processed values using the function.
    $result = $this->getResultFromForm($form_state);
    // Checking for errors in the form.
    if (!$form_state->hasAnyErrors()) {
      // Processing of values.
      foreach ($result as $tableId => $table) {
        foreach ($table as $rowId => $value) {
          // Calculating the data.
          $q1 = (($value['Jan'] + $value['Feb'] + $value['Mar']) + 1) / 3;
          $q2 = (($value['Apr'] + $value['May'] + $value['Jun']) + 1) / 3;
          $q3 = (($value['Jul'] + $value['Aug'] + $value['Sep']) + 1) / 3;
          $q4 = (($value['Oct'] + $value['Nov'] + $value['Dec']) + 1) / 3;
          $ytd = (($q1 + $q2 + $q3 + $q4) + 1) / 4;
          // Rounding of values.
          $q1 = round($q1, 2);
          $q2 = round($q2, 2);
          $q3 = round($q3, 2);
          $q4 = round($q4, 2);
          $ytd = round($ytd, 2);
          // Putting calculated values to the form.
          $form_state->setValue([$tableId, $rowId, 'Q1'], $q1);
          $form_state->setValue([$tableId, $rowId, 'Q2'], $q2);
          $form_state->setValue([$tableId, $rowId, 'Q3'], $q3);
          $form_state->setValue([$tableId, $rowId, 'Q4'], $q4);
          $form_state->setValue([$tableId, $rowId, 'YTD'], $ytd);
        }
      }
      \Drupal::messenger()->addStatus($this->t('Valid'));
    }
    else {
      \Drupal::messenger()->addError($this->t('Invalid'));
    }
    $form_state->setRebuild();
  }

}
