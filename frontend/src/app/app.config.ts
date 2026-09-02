import { provideHttpClient, withInterceptors } from '@angular/common/http';
import {
  ApplicationConfig,
  inject,
  provideAppInitializer,
  provideZoneChangeDetection,
} from '@angular/core';
import { provideRouter } from '@angular/router';
import { provideTranslateService } from '@ngx-translate/core';
import { provideTranslateHttpLoader } from '@ngx-translate/http-loader';
import { firstValueFrom } from 'rxjs';

import { apiInterceptor } from './core/interceptors/api.interceptor';
import { loadingInterceptor } from './core/interceptors/loading.interceptor';
import { LanguageService } from './core/i18n/language.service';
import { AuthService } from './core/services/auth.service';
import { routes } from './app.routes';

export const appConfig: ApplicationConfig = {
  providers: [
    provideZoneChangeDetection({ eventCoalescing: true }),
    provideHttpClient(withInterceptors([apiInterceptor, loadingInterceptor])),
    provideRouter(routes),
    provideTranslateService({ fallbackLang: 'en', lang: 'en' }),
    provideTranslateHttpLoader({
      prefix: '/assets/i18n/',
      suffix: '.json',
      useHttpBackend: true,
    }),
    provideAppInitializer(() => firstValueFrom(inject(LanguageService).initialize())),
    provideAppInitializer(() => inject(AuthService).initialize()),
  ],
};
