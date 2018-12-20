<?php

namespace Drupal\sp_field;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class Sections.
 *
 * Work with sections.
 *
 * @package Drupal\sp_field
 */
class Sections {
  use StringTranslationTrait;

  /**
   * Retrieve all sections by machine name and description.
   *
   * @return array
   *   All the sections.
   */
  public function getSections() {
    return [
      'core' => $this->t('WIOA Core'),
      'perkins' => $this->t('Career and technical education programs authorized under the Carl D. Perkins Career and
Technical Education Act of 2006 (20 U.S.C. 2301 et seq.)'),
      'tanf' => $this->t('Temporary Assistance for Needy Families Program (42 U.S.C. 601 et seq.)'),
      '' => $this->t('Employment and Training Programs under the Supplemental Nutrition Assistance Program
(Programs authorized under section 6(d)(4) of the Food and Nutrition Act of 2008 (7 U.S.C.
2015(d)(4)))'),
      'snap_et' => $this->t('Work programs authorized under section 6(o) of the Food and Nutrition Act of 2008 (7 U.S.C.
2015(o)))'),
      'taa' => $this->t('Trade Adjustment Assistance for Workers Programs (Activities authorized under chapter 2 of title II
of the Trade Act of 1974 (19 U.S.C. 2271 et seq.)) '),
      'jobsvets' => $this->t('Jobs for Veterans State Grants Program (programs authorized under 38, U.S.C. 4100 et.
seq.)'),
      'ui' => $this->t('Unemployment Insurance Programs (Programs authorized under State unemployment
compensation laws in accordance with applicable Federal law) '),
      'scsep' => $this->t('Senior Community Service Employment Program (Programs authorized under title V of the Older
Americans Act of 1965 (42 U.S.C. 3056 et seq.))'),
      'snap_work' => $this->t('Employment and training activities carried out by the Department of Housing and Urban
Development'),
      'csbg' => $this->t('Community Services Block Grant Program (Employment and training activities carried out under the
Community Services Block Grant Act (42 U.S.C. 9901 et seq.))'),
      'reo' => $this->t('Reintegration of Ex-Offenders Program (Programs authorized under section 212 of the Second
Chance Act of 2007 (42 U.S.C. 17532))]'),
    ];
  }

}
