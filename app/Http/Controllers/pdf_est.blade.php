@extends('blank')

@section('content')
  <div class="text-center">FICHA BIOANTROPOMETRICA INTEGRAL - EDUCACIÓN FÍSICA</div>
  <div class="text-section">I. DATOS GENERALES</div>

  <table>
    <tr>
      <td style="width: 40%;" class="text-label">apellidos y nombres: </td>
      <td style="width: 60%;">{{ $datos->apellidos }}, {{ $datos->nombres }}</td>
    </tr>
    <tr>
      <td class="text-label">GRADO/SECCION: </td>
      <td>{{ $datos->clase->nombre }}</td>
    </tr>
    <tr>
      <td class="text-label">FECHA DE NACIMIENTO: </td>
      <td>{{ date('d/m/Y',strtotime($datos->fecha_nacimiento)) }}</td>
    </tr>
    <tr>
      <td class="text-label">edad: </td>
      <td>{{ $datos->edad }}</td>
    </tr>
    <tr>
      <td class="text-label">dirección: </td>
      <td>{{ $datos->direccion }}</td>
    </tr>
    <tr>
      <td class="text-label">TELF/CELULAR DE URGENCIA: </td>
      <td>{{ $datos->telefono }}</td>
    </tr>
    <tr>
      <td class="text-label">PADRE/MADRE O TUTOR(A): </td>
      <td>{{ $datos->padre_tutor }}</td>
    </tr>
    <tr>
      <td class="text-label">OCUPACION DEL APODERADO: </td>
      <td>{{ $datos->ocupacion }}</td>
    </tr>
  </table>
    {{-- <div>{{ $datos->total }}</div> --}}
    
  <div class="text-section">II. DATOS ANTROPOMETRICOS:</div>

  <table class="bordes">
    <tr>
      <th style="width: 5%;" class="text-label"></th>
      <th style="width: 25%;" class="text-label">medidas</th>
      <th style="width: 10%;" class="">Entrada</th>
      <th style="width: 10%;" class="">Salida</th>
      <th style="width: 5%;" class="text-label"></th>
      <th style="width: 25%;" class="text-label">medidas</th>
      <th style="width: 10%;" class="">Entrada</th>
      <th style="width: 10%;" class="">Salida</th>
    </tr>
    <tr>
      <td class="text-label">a</td>
      <td>Talla</td>
      <td>{{ $datos->antropometrico->e_talla }}</td>
      <td>{{ $datos->antropometrico->s_talla }}</td>
      <td class="text-label">h</td>
      <td>Perímetro Torácico inspirando</td>
      <td>{{ $datos->antropometrico->e_pti }}</td>
      <td>{{ $datos->antropometrico->s_pti }}</td>
    </tr>
    <tr>
      <td class="text-label">b</td>
      <td>Peso</td>
      <td>{{ $datos->antropometrico->e_peso }}</td>
      <td>{{ $datos->antropometrico->s_peso }}</td>
      <td class="text-label">i</td>
      <td>Perímetro abdominal</td>
      <td>{{ $datos->antropometrico->e_pa }}</td>
      <td>{{ $datos->antropometrico->s_pa }}</td>
    </tr>
    <tr>
      <td class="text-label">c</td>
      <td>Índice Masa Corporal (I.M.C)</td>
      @if ($datos->antropometrico->e_peso)
      <td>{{ $datos->antropometrico->e_peso/($datos->antropometrico->e_talla*$datos->antropometrico->e_talla) }}</td>
      @else
      <td></td>
      @endif
      @if ($datos->antropometrico->s_peso)
      <td>{{ $datos->antropometrico->s_peso/($datos->antropometrico->s_talla*$datos->antropometrico->s_talla) }}</td>
      @else
      <td></td>
      @endif
      <td class="text-label">j</td>
      <td>Perímetro de muslo</td>
      <td>{{ $datos->antropometrico->e_pmuslo }}</td>
      <td>{{ $datos->antropometrico->s_pmuslo }}</td>
    </tr>
    <tr>
      <td class="text-label">d</td>
      <td>Perímetro de muñeca</td>
      <td>{{ $datos->antropometrico->e_pm }}</td>
      <td>{{ $datos->antropometrico->s_pm }}</td>
      <td class="text-label">k</td>
      <td>Perímetro de pantorrilla</td>
      <td>{{ $datos->antropometrico->e_pp }}</td>
      <td>{{ $datos->antropometrico->s_pp }}</td>
    </tr>
    <tr>
      <td class="text-label">e</td>
      <td>Perímetro de bíceps relajado</td>
      <td>{{ $datos->antropometrico->e_pb }}</td>
      <td>{{ $datos->antropometrico->s_pb }}</td>
      <td class="text-label">l</td>
      <td>Contextura corporal (G/M/P)</td>
      <td>{{ $datos->antropometrico->e_cc }}</td>
      <td>{{ $datos->antropometrico->s_cc }}</td>
    </tr>
    <tr>
      <td class="text-label">f</td>
      <td>Perímetro de tríceps contraído</td>
      <td>{{ $datos->antropometrico->e_pt }}</td>
      <td>{{ $datos->antropometrico->s_pt }}</td>
      <td class="text-label">m</td>
      <td>Pulso en reposo (1 min.)</td>
      <td>{{ $datos->antropometrico->e_plr }}</td>
      <td>{{ $datos->antropometrico->s_plr }}</td>
    </tr>
    <tr>
      <td class="text-label">g</td>
      <td>Perímetro Torácico relajado</td>
      <td>{{ $datos->antropometrico->e_ptr }}</td>
      <td>{{ $datos->antropometrico->s_ptr }}</td>
      <td class="text-label">n</td>
      <td>Pulso en actividad (1 min.)</td>
      <td>{{ $datos->antropometrico->e_pla }}</td>
      <td>{{ $datos->antropometrico->s_pla }}</td>
    </tr>
  </table>

  <div class="text-section"><span class="text-section">III. EVALUACION MEDICO-FISIOLOGICA: </span>Antecedentes Médicos</div>

  <table class="bordes">
    <tr>
      <th style="width: 30%;" class="text-label">enfermedad</th>
      <th style="width: 10%;" class="">SI</th>
      <th style="width: 10%;" class="">NO</th>
      <th style="width: 30%;" class="text-label">enfermedad</th>
      <th style="width: 10%;" class="">SI</th>
      <th style="width: 10%;" class="">NO</th>
    </tr>
    <tr>
      <td>Diabetes</td>
      @if ($datos->antecedente->diabetes)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
      <td>Desmayos</td>
      @if ($datos->antecedente->desmayos)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
    </tr>
    <tr>
      <td>Anemia</td>
      @if ($datos->antecedente->anemia)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
      <td>Convulsiones</td>
      @if ($datos->antecedente->convulsiones)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
    </tr>
    <tr>
      <td>Dificultades respiratorias (Asma)</td>
      @if ($datos->antecedente->asma)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
      <td>Hernias</td>
      @if ($datos->antecedente->hernias)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
    </tr>
    <tr>
      <td>Epilepsia</td>
      @if ($datos->antecedente->epilepsia)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
      <td>Riñones</td>
      @if ($datos->antecedente->rinones)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
    </tr>
    <tr>
      <td>Alergia</td>
      @if ($datos->antecedente->alergia)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
      <td colspan="3">A que: {{ $datos->antecedente->t_alergia }}</td>
    </tr>
    <tr>
      <td>Problemas cardiovasculares (corazón)</td>
      @if ($datos->antecedente->corazon)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
      <td colspan="3">A que: {{ $datos->antecedente->t_corazon }}</td>
    </tr>
    <tr>
      <td>Intervención Quirúrgica reciente</td>
      @if ($datos->antecedente->intervencion)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
      <td colspan="3">A que: {{ $datos->antecedente->t_intervencion }}</td>
    </tr>
    <tr>
      <td>Alteraciones de la columna vertebral</td>
      @if ($datos->antecedente->columna)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
      <td colspan="3">A que: {{ $datos->antecedente->t_columna }}</td>
    </tr>
    <tr>
      <td>Alteración  de huesos o articulaciones Deformación de los pies (plano, cavo)</td>
      @if ($datos->antecedente->a_huesos)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
      <td colspan="3">A que: {{ $datos->antecedente->t_a_huesos }}</td>
    </tr>
    <tr>
      <td>Deformación de los pies (plano, cavo)</td>
      @if ($datos->antecedente->deformacion)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
      <td colspan="3">A que: {{ $datos->antecedente->t_deformacion }}</td>
    </tr>
    <tr>
      <td>Lesión reciente</td>
      @if ($datos->antecedente->lesion)
        <td>X</td>
        <td></td>
      @else
        <td></td>
        <td>X</td>
      @endif
      <td colspan="3">A que: {{ $datos->antecedente->t_lesion }}</td>
    </tr>
    <tr>
      <td colspan="6">
        <div>Otras consideraciones que crea oportuno exponer:</div>
        <div>{{ $datos->antecedente->otras }}</div>
      </td>
    </tr>
  </table>
  <div>NOTA: No olvide que si padece algún tipo de enfermedad o dolencia que le impida desarrollar las clases con normalidad, deberá presentar justificación médica para que se tenga en cuenta a la hora de ser evaluado/ a.</div>

  <div class="text-section">IV. NIVEL DE APTITUD FISICA:</div>

  <table class="bordes">
    <tr>
      <th style="width: 60%;" class="text-label">CAPACIDADES CONDICIONALES Y COORDINATIVAS</th>
      <th style="width: 20%;" class="">ENTRADA</th>
      <th style="width: 20%;" class="">SALIDA</th>
    </tr>
    <tr>
      <th class="text-label">FLEXIBILIDAD(MEDICIÓN)</th>
      <th class="">{{ $datos->aptitud->e_flexibilidad }}</th>
      <th class="">{{ $datos->aptitud->s_flexibilidad }}</th>
    </tr>
    <tr>
      <th class="text-label">CARRERA 30 M.</th>
      <th class="">{{ $datos->aptitud->e_carrera }}</th>
      <th class="">{{ $datos->aptitud->s_carrera }}</th>
    </tr>
    <tr>
      <th class="text-label">FLEXO-EXTENSIÓN DE BRAZOS 30 SEG.</th>
      <th class="">{{ $datos->aptitud->e_flexo }}</th>
      <th class="">{{ $datos->aptitud->s_flexo }}</th>
    </tr>
    <tr>
      <th class="text-label">SALTO DE LONGITUD SIN CARRERA</th>
      <th class="">{{ $datos->aptitud->e_salto }}</th>
      <th class="">{{ $datos->aptitud->s_salto }}</th>
    </tr>
    <tr>
      <th class="text-label">RESISTENCIA 400 M.</th>
      <th class="">{{ $datos->aptitud->e_resistencia }}</th>
      <th class="">{{ $datos->aptitud->s_resistencia }}</th>
    </tr>
    <tr>
      <th class="text-label">AGILIDAD(EL SOLDADITO)	30 SEG</th>
      <th class="">{{ $datos->aptitud->e_agilidad }}</th>
      <th class="">{{ $datos->aptitud->s_agilidad }}</th>
    </tr>
    <tr>
      <th class="text-label">POTENCIA DE BRAZOS- LANZAMIENTO DE PELOTA MEDICINAL</th>
      <th class="">{{ $datos->aptitud->e_potencia }}</th>
      <th class="">{{ $datos->aptitud->s_potencia }}</th>
    </tr>
  </table>

  <div class="text-section">V. NIVEL SOCIO AFECTIVO:</div>

  {{-- "afectivos": [
    {
        "id": 136,
        "entrada": "0",
        "salida": "",
        "socioafectivo_id": 1,
        "participante_id": 26,
        "created_at": "2024-02-06T13:58:34.000000Z",
        "updated_at": "2024-02-06T13:58:34.000000Z",
        "label": {
            "id": 1,
            "nombre": "AUTÓNOMO",
            "created_at": "2024-01-31T14:28:16.000000Z",
            "updated_at": "2024-01-31T14:28:16.000000Z"
        }
    }, --}}
  <table class="bordes">
    <tr>
      <td style="width: 30%;"></td>
      <td style="width: 30%;">ENTRADA SI/NO</td>
      <td style="width: 30%;">SALIDA SI/NO</td>
    </tr>
    @foreach ($datos->afectivos as $item)
      <tr>
        <td>{{ $item->label->nombre }}</td>
        <td>
          @if ($item->entrada == '')
            
          @else
            @if ($item->entrada == '1')
              SI
            @else
              NO
            @endif
          @endif
        </td>
        <td>
          @if ($item->salida == '')
            
          @else
            @if ($item->salida == '1')
              SI
            @else
              NO
            @endif
          @endif
        </td>
      </tr>
    @endforeach
  </table>

  <div class="text-section">VI. DEPORTES DE SU PREFERENCIA:</div>

  <table class="">
    <tr>
      <td>
        <div>A. {{ $datos->deportiva->preferencia_a }}</div>
        <div>B. {{ $datos->deportiva->preferencia_b }}</div>
        <div>C. {{ $datos->deportiva->preferencia_c }}</div>
      </td>
    </tr>
  </table>
  
  <div class="text-section">VII. HISTORIA DEPORTIVA</div>

  <table class="">
    <tr>
      <td style="width: 50%;">A. Practica de manera frecuente algún deporte </td>
      <td style="width: 50%;">{{ $datos->deportiva->resp_a }}</td>
    </tr>
    <tr>
      <td>B.	Cuantos días a la semana practica </td>
      <td>{{ $datos->deportiva->resp_b }}</td>
    </tr>
    <tr>
      <td>C.	Donde practica el deporte </td>
      <td>{{ $datos->deportiva->resp_c }}</td>
    </tr>
    <tr>
      <td>D.	A qué edad se inició su vida deportiva </td>
      <td>{{ $datos->deportiva->resp_d }}</td>
    </tr>
    <tr>
      <td>E.	¿Papá y/o mamá practican deportes?  </td>
      <td>{{ $datos->deportiva->resp_e }}</td>
    </tr>
  </table>

  <div class="text-section">VIII. OBSERVACIONES DEL PROFESOR</div>
  <p>{{ $datos->deportiva->observaciones }}</p>
@endsection

@section('scripts')
  <style>
    table {
      width: 100%;
      font-size: 16px;
    }
    .text-section {
      margin-top: 10px;
      font-weight: bold;
    }
    .text-label {
      text-transform: uppercase;
    }
  </style>
@endsection