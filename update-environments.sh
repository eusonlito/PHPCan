#!/bin/bash

webs='web,intranet'
base='/home/lito/www/'
phpcan=$base'phpcan/'
yesall='n'
web='n'
nodelete='n'
updatesvn='n'
exclude='uploads,media,phpcan/cache,phpcan/logs,unittests,config/default,ANS/Db*,.git'

while true; do
	case $1 in
		-e | --exclude )
			exclude=$exclude','$2
			shift 2
			;;
		-y | --yesall )
			yesall='s'
			shift
			;;
		-w | --web )
			if [ "$2" != '' ] && [ "`echo $2 | grep -e '^-'`" = '' ] && [ "$3" != '' ]; then
				web=$2
				shift 2
			else
				web='web'
				shift
			fi

			;;
		-nd | --no-delete )
			nodelete='s'
			shift
			;;
		-us | --update-svn )
			updatesvn='s'
			shift
			;;
		-h | --help )
			echo ''
			echo 'Usage: sh '$(basename $0)' environment [-y | --yesall] [-w | --web [folder]] [-nd | --no-delete] [-us | --update-svn]'
			echo 'Options (s/n/d/p): s = yes, n = no, d = diff, p = copy to phpCan'
			echo ''

			exit 2
			;;
		* )
			if [ "`echo $1 | grep -e '^-'`" = '' ] && [ "$env" = '' ]; then
				env=$1
			else
				echo ''
				echo "No valid paramenter "$1
				echo ''

				exit 2
			fi

			break

			;;
	esac
done

#echo "-w $web\n-y $yesall\n-nd $nodelete\n-us $updatesvn\n-e $exclude\nEnvironment: $env"; exit;

if [ "$env" = '' ] || [ ! -d $base/$env ]; then
	echo ''
	echo "Non existe o entorno $base/$env"
	echo ''

	exit
fi

if [ "$web" != 'n' ] && [ ! -d $base/$env/$web ]; then
	echo ''
	echo "Non existe o directorio da escena $base/$env/$web"
	echo  ''

	exit
fi

echo ''
echo 'Procesando o entorno '$env
echo ''

newfiles=''
oldfiles=''
envpath=$base'/'$env
webs=`echo '^\\./\\('$webs'\\)/' | sed -e 's/,/\\\|/g'`
exclude=`echo '^\\./\\('$exclude'\\)/\?' | sed -e 's/,/\\\|/g'`

#echo "-w $web\n-y $yesall\n-nd $nodelete\n-us $updatesvn\n-e $exclude\nEnvironment: $env"; exit;

cd $phpcan

# Clean submodules

if [ -d phpcan/libs/imagecow/imagecow/test/ ]; then
	rm -rf phpcan/libs/imagecow/imagecow/test/
fi

if [ "`ls phpcan/libs/fzaninotto/faker/_* 2> /dev/null`" != '' ]; then
	rm -rf phpcan/libs/fzaninotto/faker/_*
fi

if [ -d phpcan/libs/fzaninotto/faker/css/ ]; then
	rm -rf phpcan/libs/fzaninotto/faker/css/
fi

if [ -d phpcan/libs/phpmailer/phpmailer/examples/ ]; then
	rm -rf phpcan/libs/phpmailer/phpmailer/examples/
fi

if [ -d phpcan/libs/phpmailer/phpmailer/docs/ ]; then
	rm -rf phpcan/libs/phpmailer/phpmailer/docs/
fi

if [ -d common/ckeditor/samples/ ]; then
	rm -rf common/ckeditor/samples/
fi

for i in `find . -type d -print | grep -v '\.svn' | grep -v '\.git' | grep -v "$webs" | grep -v "$exclude" | grep -v '\.swp' | sort`; do
	copy=''

	if [ ! -d "$envpath/$i" ]; then
		if [ "$yesall" = 's' ]; then
			copy='s'
		else
			read -p "O directorio $i non existe en $env, queres crealo? (s/n): " copy
		fi

		if [ "$copy" = 's' ]; then
			echo ''
			echo "Creando $envpath/$i"

			install -d "$envpath/$i"

			for b in `find $i/ -type f -print | grep -v '\.svn' | grep -v '\.git' | sort`; do
				if [ ! -d `dirname "$envpath/$b"` ]; then
					install -d "`dirname "$envpath/$b"`"
				fi

				echo "Copiando $b en $envpath/$b"

				cp -p "$b" "$envpath/$b"
			done

			newfiles="$newfiles $i"
		fi

		echo ''
	fi
done

for i in `find . -type f -print | grep -v '\.sh$' | grep -v '\.svn' | grep -v '\.git' | grep -v "$webs" | grep -v "$exclude" | grep -v '\.swp' | grep -v 'phpcan/config/scenes.php' | sort`; do
	copy=''

	if [ -f "$envpath/$i" ]; then
		if [ "`diff -bB $i $envpath/$i`" != '' ]; then
			if [ "$yesall" = 's' ]; then
				copy='s'
			else
				while [ "$copy" != 's' ] && [ "$copy" != 'n' ] && [ "$copy" != 'p' ]; do
					if [ "$copy" = 'd' ]; then
						diff "$i" "$envpath/$i"
					fi

					read -p "Arquivos diferentes ($i). Copiar en $envpath? (s/n/d/p): " copy

					if [ "$copy" = '' ]; then
						copy='n'
					fi
				done
			fi
		fi
	else
		if [ "$yesall" = 's' ]; then
			copy='s'
		else
			read -p "O arquivo $i non existe en $env, queres copialo? (s/n): " copy
		fi
	fi

	if [ "$copy" = 's' ]; then
		echo ''
		echo "Copiando $i en $envpath/$i"

		if [ ! -d `dirname "$envpath/$i"` ]; then
			install -d "`dirname "$envpath/$i"`"
		fi

		cp -p "$i" "$envpath/$i"

		newfiles="$newfiles $i"

		echo ''
	fi

	if [ "$copy" = 'p' ]; then
		echo ''
		echo "Copiando $envpath/$i en $i"

		if [ ! -d `dirname "$i"` ]; then
			install -d "`dirname "$i"`"
		fi

		cp -p "$envpath/$i" "$i"

		echo ''
	fi
done

if [ "$web" != 'n' ]; then
	echo ''
	echo "Comparando o entorno web con $web"
	echo ''

	for i in `find "web/includes/" "web/config/" -type f -print | grep -v 'config/default/' | grep -v 'tables.php' | grep -v 'routes.php' | grep -v 'actions.php' | grep -v 'css.php' | grep -v '\.svn' | grep -v '\.git' | grep -v '\.swp' | grep -v '/config/default/' | sort`; do
		copy=''
		new_i=`echo $i | sed -e 's/^web\//'$web'\//'`

		if [ -f "$envpath/$new_i" ]; then
			if [ "`diff -bB $i $envpath/$new_i`" != '' ]; then
				while [ "$copy" != 's' ] && [ "$copy" != 'n' ] && [ "$copy" != 'p' ]; do
					if [ "$copy" = 'd' ]; then
						diff "$i" "$envpath/$new_i"
					fi

					read -p "Arquivos diferentes ($i). Copiar en $envpath/$new_i? (s/n/d/p): " copy

					if [ "$copy" = '' ]; then
						copy='n'
					fi
				done
			fi
		else
			read -p "O arquivo $new_i non existe en $env, queres copialo? (s/n): " copy
		fi

		if [ "$copy" = 's' ]; then
			echo ''
			echo "Copiando $i en $envpath/$new_i"

			if [ ! -d `dirname "$envpath/$new_i"` ]; then
				install -d "`dirname "$envpath/$new_i"`"
			fi

			cp -p "$i" "$envpath/$new_i"

			newfiles="$newfiles $new_i"

			echo ''
		fi

		if [ "$copy" = 'p' ]; then
			echo ''
			echo "Copiando $envpath/$new_i en $i"

			if [ ! -d `dirname "$i"` ]; then
				install -d "`dirname "$i"`"
			fi

			cp -p "$envpath/$new_i" "$i"

			echo ''
		fi
	done
fi

cd $envpath

if [ "$nodelete" = 'n' ]; then
	echo ''
	echo 'Eliminando arquivos sobrantes'
	echo ''

	for i in `find . -type d -print | grep -v '\.svn' | grep -v '\.git' | grep -v "$webs" | grep -v "$exclude" | sort`; do
		if [ ! -d "$phpcan/$i" ] && [ -d "`dirname "$phpcan/$i"`" ]; then
			read -p "O directorio $i non existe no phpcan, eliminar de $env? (s/n/p): " delete

			if [ "$delete" = 's' ]; then
				echo ''
				echo "Eliminando $i"

				rm -rf $i

				oldfiles="$oldfiles $i"
			fi

			if [ "$delete" = 'p' ]; then
				echo ''
				echo "Creando $phpcan/$i"

				install -d "$phpcan/$i"

				echo ''
			fi
		fi
	done

	for i in `find . -type f -print | grep -v '\.sh$' | grep -v '\.svn' | grep -v '\.git' | grep -v "$webs" | grep -v "$exclude" | grep -v '/common' | sort`; do
		if [ ! -f "$phpcan/$i" ]; then
			read -p "O arquivo $i non existe no phpcan, eliminar de $env? (s/n/p): " delete

			if [ "$delete" = 's' ]; then
				echo ''
				echo "Eliminando $i"

				rm -I $i

				oldfiles="$oldfiles $i"
			fi

			if [ "$delete" = 'p' ]; then
				echo ''
				echo "Copiando $i en $phpcan/$i"

				if [ ! -d `dirname "$phpcan/$i"` ]; then
					install -d "`dirname "$phpcan/$i"`"
				fi

				cp -p "$i" "$phpcan/$i"

				echo ''
			fi
		fi
	done
fi

if [ "$updatesvn" = 's' ]; then
	echo ''
	echo 'Actualizando o SVN'
	echo ''

	svn update

	if [ "$newfiles" != '' ]; then
		svn add -q $newfiles
	fi

	if [ "$oldfiles" != '' ]; then
		svn del -q $oldfiles
	fi

	svn commit -m 'Updated phpCan'

	echo ''
	echo 'Estado do entorno'
	echo ''

	svn status
fi

echo ''
echo 'Entorno actualizado'
echo ''
