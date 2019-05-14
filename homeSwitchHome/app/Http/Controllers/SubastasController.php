<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Subasta;
use App\Participa;

class SubastasController extends Controller
{
    public function crearSubasta($idHospedaje){
        $hospedaje = DB::table('hospedajes')
                    ->where('id', $idHospedaje)
                    ->first();

        $data['titulo'] = $hospedaje->titulo;
        $data['idHospedaje'] = $hospedaje->id;
        $data['fechaInicioHospedaje'] = $hospedaje->fecha_inicio;   
        $data['fechaFinHospedaje'] = $hospedaje->fecha_fin;          

        return view('crearSubasta', $data);            
    }  


    public function validar(Request $request){

        $fechaInicio = Carbon::create($request->input('fechaInicio'));
        $fechaFin = Carbon::create($request->input('fechaInicio'))->addDays(7)->format('Y-m-d');

        $request['fechaFin'] = $fechaFin;

        $request->validate([
            'montoBase' => 'required|numeric',
            'fechaInicio' => 'required|after:fechaInicioHospedaje',
            'fechaFin' => 'before:fechaFinHospedaje'],
            ['montoBase.required' => 'Por favor ingrese un monto base', 
             'montoBase.numeric' => 'Por favor ingrese un valor numérico',
             'fechaInicio.required' => 'Por favor ingrese una fecha de inicio',
             'fechaInico.after' => 'La fecha de inicio debe estar dentro del rango libre del Hospedaje',
             'fechaFin.before' => 'La fecha de fin debe estar dentro del rango libre del Hospedaje'
               ]);

        

    	$subasta = new Subasta;
    	$subasta->monto_base = $request->input('montoBase');
    	$subasta->fecha_inicio = $fechaInicio;
        $subasta->fecha_fin = $fechaFin;
    	$subasta->id_hospedaje = $request->input('idHospedaje');

    	$subasta->save();

    	return redirect('/');
    }

    public function detalleSubasta($idSubasta){

        $subasta = DB::table('subastas')->where('id', $idSubasta)->first();
        $hospedaje = DB::table('hospedajes')->where('id', $subasta->id_hospedaje)->first();
                
        $data['tituloHospedaje'] = $hospedaje->titulo;
        $data['maximasPersonas'] = $hospedaje->cantidad_maxima_personas; 
        $data['descripcion'] = $hospedaje->descripcion;    
        $data['nombreImagen'] = 'Logo.png';   
        $data['idSubasta'] = $subasta->id;
        $data['montoBase'] = $subasta->monto_base;
        $data['fechaInicio'] = $subasta->fecha_inicio;
        $data['fechaFin'] = $subasta->fecha_fin;

        $maximaPuja = DB::table('participas')
                    ->select('id_usuario', DB::raw('MAX(puja) as puja'))
                    ->where('id_subasta', $subasta->id)
                    ->groupBy('id_subasta')
                    ->first();

        if(is_null($maximaPuja)){
            $data['maximoUsuario'] = 'no hay usuario';
            $data['montoMaximo'] = 0;
        }
        else {
            $maximoUsuario = DB::table('usuarios')
                    ->where('id', $maximaPuja->id_usuario)
                    ->first();
            $data['maximoUsuario'] = $maximoUsuario->email;
            $data['montoMaximo'] = $maximaPuja->puja;               
        }
        return view('detalleSubasta', $data);
    }

    public function pujar(Request $request){

        if($request->input('montoMaximo') == 0)
            $request->validate([
                'valorPuja' => 'required|numeric|bail|gt:montoBase'],
                ['valorPuja.required' => 'Por favor ingrese un monto a pujar', 
                 'valorPuja.numeric' => 'Por favor ingrese un valor numérico',
                  'valorPuja.gt' => 'El valor debe ser mas grande que la puja máxima']);
        else
            $request->validate([
                'valorPuja' => 'required|numeric|bail|gt:montoMaximo'],
                ['valorPuja.required' => 'Por favor ingrese un monto a pujar',
                 'valorPuja.numeric' => 'Por favor ingrese un valor numérico',
                'valorPuja.gt' => 'El valor debe ser mas grande que la puja máxima']);

        $puja = new Participa;
        $puja->puja = $request->input('valorPuja');
        $puja->id_subasta = $request->input('idSubasta');
        $puja->id_usuario = 1;

        $puja->save();

        
        $id = $request->input('idSubasta');
        return redirect()->route('cargardetallesubasta', [$id]);
    }

    public function listarSubastas(Request $request){

        //return $request->route('nombreParametro');

        $data['subastas'] = DB::table('subastas')->get();
        

        return view('/layouts/listarSubastas', $data);
    }
}