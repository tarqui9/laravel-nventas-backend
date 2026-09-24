<?php

namespace App\Http\Controllers;

use PDF;
use Auth;
use App\Models\Aptitud;
use App\Models\Afectivo;
use App\Models\Deportiva;
use App\Models\Antecedente;
use App\Models\Participante;
use Illuminate\Http\Request;
use App\Models\Antropometrico;

class ParticipanteController extends Controller
{
    public function index(Request $request) {
        $term = $request->get('term');
        $clase_id = $request->get('clase_id');
        $data = Participante::from('participantes as p')
        ->join('clases as c','c.id','p.clase_id')
        ->where('c.user_id',Auth::user()->id)
        ->sterm($term,'p.')
        ->sclase($clase_id,'p.')
        ->select('p.*')
        ->latest()
        // ->with(['aptitud','afectivos','deportiva'])->get();
        ->with(['clase','antropometrico','antecedente','aptitud','afectivos','deportiva'])->get();
        return response()->json($data, 200);
    }
    public function show($id) {
        $data = Participante::with(['clase','antropometrico','antecedente','aptitud','afectivos','deportiva'])->find($id);
        return response()->json($data, 200);
    }
    public function store(Request $request) {
        // no Categorias repetidos
        // $valid = Participante::where('ci',$request->ci)->first();
        // if ($valid) {
        //     return response()->json('El CI ya fue registrado.', 409);
        // }
        $name = null;
        $dir_folder = 'images/profile';
        if($request->hasFile('file')){
            if (!is_dir(public_path().'/'.$dir_folder)) {
                $crearDir = mkdir(public_path().'/'.$dir_folder, 0777, true);
            }
            $file = $request->file('file');
            //concatena la hora y el nombre del archivo
            $name = time().$file->getClientOriginalName();
            $tempPath = $file->getPathName();

            // Leer la orientación EXIF de la imagen y ajustarla
            $image = imagecreatefromstring(file_get_contents($tempPath));
            $exif = exif_read_data($tempPath);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;
                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;
                }
            }
            // $file->move(public_path().'/'.$dir_folder, $name);
            $ruta = public_path().'/'.$dir_folder.'/'. $name;
            switch ($file->getClientMimeType()) {
                case 'image/jpeg':
                    imagejpeg($image, $ruta);
                    break;
                case 'image/png':
                    imagepng($image, $ruta);
                    break;
                case 'image/gif':
                    imagegif($image, $ruta);
                    break;
            }
            // Liberar memoria
            imagedestroy($image);
        }
        $newData = new Participante;
        $newData->nombres = $request->nombres;
        $newData->apellidos = $request->apellidos;
        $newData->fecha_nacimiento = $request->fecha_nacimiento;
        $newData->direccion = $request->direccion;
        $newData->telefono = $request->telefono;
        $newData->padre_tutor = $request->padre_tutor;
        $newData->ocupacion = $request->ocupacion;
        $newData->ea_fecha = $request->ea_fecha;
        $newData->sa_fecha = $request->sa_fecha;
        if ($name) {
            $newData->img_url = $dir_folder.'/'.$name;
        }
        $newData->habilitado = true;
        $newData->clase_id = $request->clase_id?$request->clase_id:1;
        $newData->save();
        
        $newAntro = new Antropometrico;
        $newAntro->e_talla = $request->e_talla;
        $newAntro->s_talla = $request->s_talla;
        $newAntro->e_peso = $request->e_peso;
        $newAntro->s_peso = $request->s_peso;
        $newAntro->e_pm = $request->e_pm;
        $newAntro->s_pm = $request->s_pm;
        $newAntro->e_pb = $request->e_pb;
        $newAntro->s_pb = $request->s_pb;
        $newAntro->e_pt = $request->e_pt;
        $newAntro->s_pt = $request->s_pt;
        $newAntro->e_ptr = $request->e_ptr;
        $newAntro->s_ptr = $request->s_ptr;
        $newAntro->e_pti = $request->e_pti;
        $newAntro->s_pti = $request->s_pti;
        $newAntro->e_pa = $request->e_pa;
        $newAntro->s_pa = $request->s_pa;
        $newAntro->e_pmuslo = $request->e_pmuslo;
        $newAntro->s_pmuslo = $request->s_pmuslo;
        $newAntro->e_pp = $request->e_pp;
        $newAntro->s_pp = $request->s_pp;
        $newAntro->e_cc = $request->e_cc;
        $newAntro->s_cc = $request->s_cc;
        $newAntro->e_plr = $request->e_plr;
        $newAntro->s_plr = $request->s_plr;
        $newAntro->e_pla = $request->e_pla;
        $newAntro->s_pla = $request->s_pla;
        $newAntro->participante_id = $newData->id;
        $newAntro->save();
        $newAnte = new Antecedente;
        $newAnte->diabetes = $request->diabetes?$request->diabetes:false;
        $newAnte->anemia = $request->anemia?$request->anemia:false;
        $newAnte->asma = $request->asma?$request->asma:false;
        $newAnte->apilepsia = $request->apilepsia?$request->apilepsia:false;
        $newAnte->alergia = $request->alergia?$request->alergia:false;
        $newAnte->t_alergia = $request->t_alergia;
        $newAnte->corazon = $request->corazon?$request->corazon:false;
        $newAnte->t_corazon = $request->t_corazon;
        $newAnte->intervencion = $request->intervencion?$request->intervencion:false;
        $newAnte->t_intervencion = $request->t_intervencion;
        $newAnte->columna = $request->columna?$request->columna:false;
        $newAnte->t_columna = $request->t_columna;
        $newAnte->a_huesos = $request->a_huesos?$request->a_huesos:false;
        $newAnte->t_a_huesos = $request->t_a_huesos;
        $newAnte->deformacion = $request->deformacion?$request->deformacion:false;
        $newAnte->t_deformacion = $request->t_deformacion;
        $newAnte->lesion = $request->lesion?$request->lesion:false;
        $newAnte->t_lesion = $request->t_lesion;
        $newAnte->desmayos = $request->desmayos?$request->desmayos:false;
        $newAnte->convulsiones = $request->convulsiones?$request->convulsiones:false;
        $newAnte->hernias = $request->hernias?$request->hernias:false;
        $newAnte->rinones = $request->rinones?$request->rinones:false;
        $newAnte->otras = $request->otras;
        $newAnte->participante_id = $newData->id;
        $newAnte->save();
        $newDep = new Deportiva;
        $newDep->preferencia_a = $request->preferencia_a;
        $newDep->preferencia_b = $request->preferencia_b;
        $newDep->preferencia_c = $request->preferencia_c;
        $newDep->resp_a = $request->resp_a;
        $newDep->resp_b = $request->resp_b;
        $newDep->resp_c = $request->resp_c;
        $newDep->resp_d = $request->resp_d;
        $newDep->resp_e = $request->resp_e;
        $newDep->observaciones = $request->observaciones;
        $newDep->participante_id = $newData->id;
        $newDep->save();
        $newAp = new Aptitud;
        $newAp->e_fecha = $request->e_fecha;
        $newAp->s_fecha = $request->s_fecha;
        $newAp->e_flexibilidad = $request->e_flexibilidad;
        $newAp->s_flexibilidad = $request->s_flexibilidad;
        $newAp->e_carrera = $request->e_carrera;
        $newAp->s_carrera = $request->s_carrera;
        $newAp->e_flexo = $request->e_flexo;
        $newAp->s_flexo = $request->s_flexo;
        $newAp->e_salto = $request->e_salto;
        $newAp->s_salto = $request->s_salto;
        $newAp->e_resistencia = $request->e_resistencia;
        $newAp->s_resistencia = $request->s_resistencia;
        $newAp->e_agilidad = $request->e_agilidad;
        $newAp->s_agilidad = $request->s_agilidad;
        $newAp->e_potencia = $request->e_potencia;
        $newAp->s_potencia = $request->s_potencia;
        $newAp->participante_id = $newData->id;
        $newAp->save();

        $afectivos = json_decode($request->afectivos);
        foreach ($afectivos as $value) {
            $newAf = new Afectivo;
            $newAf->entrada = $value->entrada;
            $newAf->salida = $value->salida;
            $newAf->socioafectivo_id = $value->id;
            $newAf->participante_id = $newData->id;
            $newAf->save();
        }
        $data = [
            'success' => 'Se ha realizado un nuevo registro.'
        ];
        return response()->json($data, 201);
    }
    public function update(Request $request, $id) {
        $editData = Participante::find($id);
        if (!$editData) {
            return response()->json('Ha ocurrido un error.', 404);
        }
        $name = null;
        $dir_folder = 'images/profile';
        if($request->file != 'undefined' && $request->hasFile('file')){
            if (!is_dir(public_path().'/'.$dir_folder)) {
                $crearDir = mkdir(public_path().'/'.$dir_folder, 0777, true);
            }
            $file = $request->file('file');
            //concatena la hora y el nombre del archivo
            $name = time().$file->getClientOriginalName();
            $tempPath = $file->getPathName();

            // Leer la orientación EXIF de la imagen y ajustarla
            $image = imagecreatefromstring(file_get_contents($tempPath));
            $exif = exif_read_data($tempPath);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;
                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;
                }
            }
            // $file->move(public_path().'/'.$dir_folder, $name);
            $ruta = public_path().'/'.$dir_folder.'/'. $name;
            switch ($file->getClientMimeType()) {
                case 'image/jpeg':
                    imagejpeg($image, $ruta);
                    break;
                case 'image/png':
                    imagepng($image, $ruta);
                    break;
                case 'image/gif':
                    imagegif($image, $ruta);
                    break;
            }
            // Liberar memoria
            imagedestroy($image);
        }
        if ($name) {
            $editData->img_url = $dir_folder.'/'.$name;
        }
        $editData->nombres = $request->nombres!='undefined'?$request->nombres:null;
        $editData->apellidos = $request->apellidos!='undefined'?$request->apellidos:null;
        $editData->fecha_nacimiento = $request->fecha_nacimiento!='undefined'?$request->fecha_nacimiento:null;
        $editData->direccion = $request->direccion!='undefined'?$request->direccion:null;
        $editData->telefono = $request->telefono!='undefined'?$request->telefono:null;
        $editData->padre_tutor = $request->padre_tutor!='undefined'?$request->padre_tutor:null;
        $editData->ocupacion = $request->ocupacion!='undefined'?$request->ocupacion:null;
        $editData->ea_fecha = $request->ea_fecha!='undefined'?$request->ea_fecha:null;
        $editData->sa_fecha = $request->sa_fecha!='undefined'?$request->sa_fecha:null;
        $editData->clase_id = $request->clase_id?$request->clase_id:1;
        $editData->save();
        
        $editData->antropometrico->e_talla = $request->e_talla!='undefined'?$request->e_talla:null;
        $editData->antropometrico->s_talla = $request->s_talla!='undefined'?$request->s_talla:null;
        $editData->antropometrico->e_peso = $request->e_peso!='undefined'?$request->e_peso:null;
        $editData->antropometrico->s_peso = $request->s_peso!='undefined'?$request->s_peso:null;
        $editData->antropometrico->e_pm = $request->e_pm!='undefined'?$request->e_pm:null;
        $editData->antropometrico->s_pm = $request->s_pm!='undefined'?$request->s_pm:null;
        $editData->antropometrico->e_pb = $request->e_pb!='undefined'?$request->e_pb:null;
        $editData->antropometrico->s_pb = $request->s_pb!='undefined'?$request->s_pb:null;
        $editData->antropometrico->e_pt = $request->e_pt!='undefined'?$request->e_pt:null;
        $editData->antropometrico->s_pt = $request->s_pt!='undefined'?$request->s_pt:null;
        $editData->antropometrico->e_ptr = $request->e_ptr!='undefined'?$request->e_ptr:null;
        $editData->antropometrico->s_ptr = $request->s_ptr!='undefined'?$request->s_ptr:null;
        $editData->antropometrico->e_pti = $request->e_pti!='undefined'?$request->e_pti:null;
        $editData->antropometrico->s_pti = $request->s_pti!='undefined'?$request->s_pti:null;
        $editData->antropometrico->e_pa = $request->e_pa!='undefined'?$request->e_pa:null;
        $editData->antropometrico->s_pa = $request->s_pa!='undefined'?$request->s_pa:null;
        $editData->antropometrico->e_pmuslo = $request->e_pmuslo!='undefined'?$request->e_pmuslo:null;
        $editData->antropometrico->s_pmuslo = $request->s_pmuslo!='undefined'?$request->s_pmuslo:null;
        $editData->antropometrico->e_pp = $request->e_pp!='undefined'?$request->e_pp:null;
        $editData->antropometrico->s_pp = $request->s_pp!='undefined'?$request->s_pp:null;
        $editData->antropometrico->e_cc = $request->e_cc!='undefined'?$request->e_cc:null;
        $editData->antropometrico->s_cc = $request->s_cc!='undefined'?$request->s_cc:null;
        $editData->antropometrico->e_plr = $request->e_plr!='undefined'?$request->e_plr:null;
        $editData->antropometrico->s_plr = $request->s_plr!='undefined'?$request->s_plr:null;
        $editData->antropometrico->e_pla = $request->e_pla!='undefined'?$request->e_pla:null;
        $editData->antropometrico->s_pla = $request->s_pla!='undefined'?$request->s_pla:null;
        $editData->antropometrico->save();

        if ($request->diabetes != 'undefined') {
            $editData->antecedente->diabetes = $request->diabetes?$request->diabetes:false;
        }
        if ($request->anemia != 'undefined') {
            $editData->antecedente->anemia = $request->anemia?$request->anemia:false;
        }
        if ($request->asma != 'undefined') {
            $editData->antecedente->asma = $request->asma?$request->asma:false;
        }
        if ($request->apilepsia != 'undefined') {
            $editData->antecedente->apilepsia = $request->apilepsia?$request->apilepsia:false;
        }
        if ($request->alergia != 'undefined') {
            $editData->antecedente->alergia = $request->alergia?$request->alergia:false;
        }
        $editData->antecedente->t_alergia = $request->t_alergia!='undefined'?$request->t_alergia:null;
        if ($request->corazon != 'undefined') {
            $editData->antecedente->corazon = $request->corazon?$request->corazon:false;
        }
        $editData->antecedente->t_corazon = $request->t_corazon!='undefined'?$request->t_corazon:null;
        if ($request->intervencion != 'undefined') {
            $editData->antecedente->intervencion = $request->intervencion?$request->intervencion:false;
        }
        $editData->antecedente->t_intervencion = $request->t_intervencion!='undefined'?$request->t_intervencion:null;
        if ($request->columna != 'undefined') {
            $editData->antecedente->columna = $request->columna?$request->columna:false;
        }
        $editData->antecedente->t_columna = $request->t_columna!='undefined'?$request->t_columna:null;
        if ($request->a_huesos != 'undefined') {
            $editData->antecedente->a_huesos = $request->a_huesos?$request->a_huesos:false;
        }
        $editData->antecedente->t_a_huesos = $request->t_a_huesos!='undefined'?$request->t_a_huesos:null;
        if ($request->deformacion != 'undefined') {
            $editData->antecedente->deformacion = $request->deformacion?$request->deformacion:false;
        }
        $editData->antecedente->t_deformacion = $request->t_deformacion!='undefined'?$request->t_deformacion:null;
        if ($request->lesion != 'undefined') {
            $editData->antecedente->lesion = $request->lesion?$request->lesion:false;
        }
        $editData->antecedente->t_lesion = $request->t_lesion!='undefined'?$request->t_lesion:null;
        if ($request->desmayos != 'undefined') {
            $editData->antecedente->desmayos = $request->desmayos?$request->desmayos:false;
        }
        if ($request->convulsiones != 'undefined') {
            $editData->antecedente->convulsiones = $request->convulsiones?$request->convulsiones:false;
        }
        if ($request->hernias != 'undefined') {
            $editData->antecedente->hernias = $request->hernias?$request->hernias:false;
        }
        if ($request->rinones != 'undefined') {
            $editData->antecedente->rinones = $request->rinones?$request->rinones:false;
        }
        $editData->antecedente->otras = $request->otras!='undefined'?$request->otras:null;
        $editData->antecedente->save();

        $editData->deportiva->preferencia_a = $request->preferencia_a!='undefined'?$request->preferencia_a:null;
        $editData->deportiva->preferencia_b = $request->preferencia_b!='undefined'?$request->preferencia_b:null;
        $editData->deportiva->preferencia_c = $request->preferencia_c!='undefined'?$request->preferencia_c:null;
        $editData->deportiva->resp_a = $request->resp_a!='undefined'?$request->resp_a:null;
        $editData->deportiva->resp_b = $request->resp_b!='undefined'?$request->resp_b:null;
        $editData->deportiva->resp_c = $request->resp_c!='undefined'?$request->resp_c:null;
        $editData->deportiva->resp_d = $request->resp_d!='undefined'?$request->resp_d:null;
        $editData->deportiva->resp_e = $request->resp_e!='undefined'?$request->resp_e:null;
        $editData->deportiva->observaciones = $request->observaciones!='undefined'?$request->observaciones:null;
        $editData->deportiva->save();

        $editData->aptitud->e_fecha = $request->e_fecha!='undefined'?$request->e_fecha:null;
        $editData->aptitud->s_fecha = $request->s_fecha!='undefined'?$request->s_fecha:null;
        $editData->aptitud->e_flexibilidad = $request->e_flexibilidad!='undefined'?$request->e_flexibilidad:null;
        $editData->aptitud->s_flexibilidad = $request->s_flexibilidad!='undefined'?$request->s_flexibilidad:null;
        $editData->aptitud->e_carrera = $request->e_carrera!='undefined'?$request->e_carrera:null;
        $editData->aptitud->s_carrera = $request->s_carrera!='undefined'?$request->s_carrera:null;
        $editData->aptitud->e_flexo = $request->e_flexo!='undefined'?$request->e_flexo:null;
        $editData->aptitud->s_flexo = $request->s_flexo!='undefined'?$request->s_flexo:null;
        $editData->aptitud->e_salto = $request->e_salto!='undefined'?$request->e_salto:null;
        $editData->aptitud->s_salto = $request->s_salto!='undefined'?$request->s_salto:null;
        $editData->aptitud->e_resistencia = $request->e_resistencia!='undefined'?$request->e_resistencia:null;
        $editData->aptitud->s_resistencia = $request->s_resistencia!='undefined'?$request->s_resistencia:null;
        $editData->aptitud->e_agilidad = $request->e_agilidad!='undefined'?$request->e_agilidad:null;
        $editData->aptitud->s_agilidad = $request->s_agilidad!='undefined'?$request->s_agilidad:null;
        $editData->aptitud->e_potencia = $request->e_potencia!='undefined'?$request->e_potencia:null;
        $editData->aptitud->s_potencia = $request->s_potencia!='undefined'?$request->s_potencia:null;
        $editData->aptitud->save();

        $afectivos = json_decode($request->afectivos);
        foreach ($afectivos as $value) {
            $newAf = Afectivo::where('participante_id',$id)
            ->where('id',$value->id)
            ->first();
            if ($newAf) {
                $newAf->entrada = $value->entrada != 'undefined'?$value->entrada:$newAf->entrada;
                $newAf->salida = $value->entrada != 'undefined'?$value->salida:$newAf->salida;
                // $newAf->socioafectivo_id = $value->id;
                $newAf->save();
            }
        }

        $data = [
            'success' => 'Se ha actualizado el registro.'
        ];
        return response()->json($data, 200);
    }
    public function destroy($id) {
        $data = Participante::find($id);
        if (!$data) {
            return response()->json($data, 409);
        }
        // $valid = Producto::where('categoria_id',$id)->first();
        // if ($valid) {
        //     return response()->json('No puede ser eliminado', 409);
        // }
        $data->delete();
        return response()->json($data, 200);
    }

    public function habilitar($id) {
        $item = Participante::find($id);
        $text = 'habilitado.';
        if ($item->habilitado) {
            $item->habilitado = false;
            $text = 'deshabilitado.';
        } else {
            $item->habilitado = true;
        }
        $item->save();
        return response()->json(['success' => 'Item '.$text], 200);
    }
    public function habilitados() {
        $data = Participante::habilitado()->get();
        return response()->json($data, 200);
    }
    public function pdf($id) {
        $query = Participante::find($id);
        $data = [
            'datos' => $query,
        ];
        $pdf = PDF::loadView('reportes.pdf_est', $data);
        return $pdf->stream('reporte.pdf');
    }
}
